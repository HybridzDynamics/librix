import csv
import os
import re
import sys
import unicodedata
import mysql.connector
from mysql.connector import Error

CSV_FILE = "books_clean.csv"

DB_HOST = os.getenv("LIBRIX_DB_HOST", "localhost")
DB_PORT = int(os.getenv("LIBRIX_DB_PORT", "3306"))
DB_NAME = os.getenv("LIBRIX_DB_NAME", "librix")
DB_USER = os.getenv("LIBRIX_DB_USER", "root")
DB_PASSWORD = os.getenv("LIBRIX_DB_PASSWORD", "admin")

BATCH_SIZE = 1000


def clean_text(value):
    if value is None:
        return ""

    value = str(value).strip()
    value = re.sub(r"\s+", " ", value)

    return value


def normalize_key(value):
    value = clean_text(value)
    value = unicodedata.normalize("NFKC", value)
    return value.casefold()


def clean_language(value):
    value = clean_text(value)

    if not value:
        return "English"

    language_map = {
        "eng": "English",
        "en-US": "English",
        "en-GB": "English",
        "fre": "French",
        "fra": "French",
        "spa": "Spanish",
        "ger": "German",
        "deu": "German",
        "ita": "Italian",
        "por": "Portuguese",
        "rus": "Russian",
        "jpn": "Japanese",
        "zho": "Chinese",
        "chi": "Chinese",
    }

    return language_map.get(value, value[:50])


def split_genres(value):
    value = clean_text(value)

    if not value:
        return []

    genres = []
    seen = set()

    for item in value.split(","):
        item = clean_text(item)

        if not item:
            continue

        key = normalize_key(item)

        if key not in seen:
            seen.add(key)
            genres.append(item[:100])

    return genres


def get_connection():
    return mysql.connector.connect(
        host=DB_HOST,
        port=DB_PORT,
        database=DB_NAME,
        user=DB_USER,
        password=DB_PASSWORD
    )


def ensure_extra_columns(cursor):
    columns = {
        "average_rating": """
            ALTER TABLE books
            ADD COLUMN average_rating DECIMAL(3,2) NULL
        """,
        "rating_count": """
            ALTER TABLE books
            ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0
        """,
        "content": """
            ALTER TABLE books
            ADD COLUMN content LONGTEXT NULL
        """
    }

    for column, query in columns.items():
        cursor.execute("SHOW COLUMNS FROM books LIKE %s", (column,))

        if not cursor.fetchone():
            cursor.execute(query)


def load_existing_authors(cursor):
    cursor.execute("SELECT id, name FROM authors")

    cache = {}

    for author_id, name in cursor.fetchall():
        cache[normalize_key(name)] = author_id

    return cache


def load_existing_categories(cursor):
    cursor.execute("SELECT id, name FROM categories")

    cache = {}

    for category_id, name in cursor.fetchall():
        cache[normalize_key(name)] = category_id

    return cache


def create_or_get_author(cursor, author_name, author_cache):
    key = normalize_key(author_name)

    if key in author_cache:
        return author_cache[key]

    cursor.execute(
        "SELECT id FROM authors WHERE name = %s LIMIT 1",
        (author_name,)
    )

    result = cursor.fetchone()

    if result:
        author_id = result[0]
        author_cache[key] = author_id
        return author_id

    author_name = author_name[:150]

    try:
        cursor.execute(
            "INSERT INTO authors (name) VALUES (%s)",
            (author_name,)
        )

        author_id = cursor.lastrowid
        author_cache[key] = author_id

        return author_id

    except mysql.connector.IntegrityError:
        cursor.execute(
            "SELECT id FROM authors WHERE name = %s LIMIT 1",
            (author_name,)
        )

        result = cursor.fetchone()

        if result:
            author_id = result[0]
            author_cache[key] = author_id
            return author_id

        raise


def create_or_get_category(cursor, category_name, category_cache):
    key = normalize_key(category_name)

    if key in category_cache:
        return category_cache[key]

    category_name = category_name[:100]

    cursor.execute(
        "SELECT id FROM categories WHERE name = %s LIMIT 1",
        (category_name,)
    )

    result = cursor.fetchone()

    if result:
        category_id = result[0]
        category_cache[key] = category_id
        return category_id

    try:
        cursor.execute(
            "INSERT INTO categories (name) VALUES (%s)",
            (category_name,)
        )

        category_id = cursor.lastrowid
        category_cache[key] = category_id

        return category_id

    except mysql.connector.IntegrityError:
        cursor.execute(
            "SELECT id FROM categories WHERE name = %s LIMIT 1",
            (category_name,)
        )

        result = cursor.fetchone()

        if result:
            category_id = result[0]
            category_cache[key] = category_id
            return category_id

        raise


def insert_book(cursor, book):
    cursor.execute("""
        INSERT INTO books (
            author_id,
            category_id,
            publisher_id,
            title,
            isbn,
            description,
            category,
            language,
            publisher,
            publication_year,
            total_copies,
            available_copies,
            cover_image,
            average_rating,
            rating_count,
            content
        )
        VALUES (
            %s, %s, NULL, %s, %s, %s, %s, %s, NULL, NULL,
            1, 1, %s, %s, %s, %s
        )
    """, (
        book["author_id"],
        book["category_id"],
        book["title"],
        book["isbn"],
        book["description"],
        book["all_genres"],
        book["language"],
        book["cover_image"],
        book["rating"],
        book["rating_count"],
        book["content"]
    ))


def book_exists(cursor, title):
    cursor.execute(
        "SELECT id FROM books WHERE title = %s LIMIT 1",
        (title,)
    )

    return cursor.fetchone() is not None


def main():
    if not os.path.exists(CSV_FILE):
        print("ERROR: books_clean.csv was not found.")
        print("Put this script in the same folder as books_clean.csv.")
        sys.exit(1)

    connection = None

    try:
        connection = get_connection()

        if not connection.is_connected():
            print("ERROR: Could not connect to MySQL.")
            sys.exit(1)

        cursor = connection.cursor()

        print("Connected to LibriX database.")
        print("Preparing database...")

        ensure_extra_columns(cursor)
        connection.commit()

        author_cache = load_existing_authors(cursor)
        category_cache = load_existing_categories(cursor)

        print("Existing authors:", len(author_cache))
        print("Existing categories:", len(category_cache))
        print()

        inserted = 0
        skipped = 0
        already_exists = 0
        failed = 0

        with open(CSV_FILE, "r", encoding="utf-8-sig", newline="") as file:
            reader = csv.DictReader(file)

            for row_number, row in enumerate(reader, start=2):
                try:
                    title = clean_text(row.get("title"))
                    author_name = clean_text(row.get("author"))
                    genres = split_genres(row.get("genres"))
                    description = clean_text(row.get("description"))
                    language = clean_language(row.get("language_code"))
                    image_url = clean_text(row.get("image_url"))
                    content = clean_text(row.get("content"))

                    if not title or not author_name:
                        skipped += 1
                        continue

                    if book_exists(cursor, title):
                        already_exists += 1
                        continue

                    author_id = create_or_get_author(
                        cursor,
                        author_name,
                        author_cache
                    )

                    primary_category_id = None

                    if genres:
                        primary_category_id = create_or_get_category(
                            cursor,
                            genres[0],
                            category_cache
                        )

                    try:
                        rating = float(clean_text(row.get("rating")))
                    except (ValueError, TypeError):
                        rating = None

                    try:
                        rating_count = int(float(clean_text(row.get("numRatings"))))
                    except (ValueError, TypeError):
                        rating_count = 0

                    book = {
                        "author_id": author_id,
                        "category_id": primary_category_id,
                        "title": title[:255],
                        "isbn": None,
                        "description": description or None,
                        "all_genres": ", ".join(genres) if genres else None,
                        "language": language,
                        "cover_image": image_url or None,
                        "rating": rating,
                        "rating_count": rating_count,
                        "content": content or None
                    }

                    insert_book(cursor, book)
                    inserted += 1

                    if inserted % BATCH_SIZE == 0:
                        connection.commit()
                        print("Imported:", inserted)

                except Exception as error:
                    connection.rollback()

                    # Re-load caches because the rollback may have removed
                    # an author/category inserted during this row.
                    author_cache = load_existing_authors(cursor)
                    category_cache = load_existing_categories(cursor)

                    failed += 1
                    print("Failed row", row_number, ":", error)

        connection.commit()

        print()
        print("========================================")
        print("         LIBRIX IMPORT COMPLETE")
        print("========================================")
        print("Books inserted :", inserted)
        print("Already existed :", already_exists)
        print("Books skipped   :", skipped)
        print("Books failed    :", failed)
        print("Authors         :", len(author_cache))
        print("Categories      :", len(category_cache))
        print("========================================")

    except Error as error:
        print("DATABASE ERROR:")
        print(error)

        if connection:
            connection.rollback()

    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()
            print("Database connection closed.")


if __name__ == "__main__":
    main()
