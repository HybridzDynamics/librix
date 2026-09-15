#!/usr/bin/env python3
"""
import_librix_books.py
LibriX CSV to MySQL importer — fully rewritten

Usage:
    python import_librix_books.py [OPTIONS]

Options:
    --csv      PATH     Path to CSV file          (default: books_clean.csv)
    --org      INT      Assign all books to org_id (default: NULL = global)
    --limit    INT      Only import first N rows   (default: all)
    --dry-run           Parse/validate without writing to DB
    --host     STR      MySQL host                 (default: localhost)
    --port     INT      MySQL port                 (default: 3306)
    --db       STR      Database name              (default: librix)
    --user     STR      MySQL user                 (default: root)
    --password STR      MySQL password             (default: admin)

Examples:
    python import_librix_books.py
    python import_librix_books.py --org 1 --limit 500
    python import_librix_books.py --dry-run --limit 100
    python import_librix_books.py --host 127.0.0.1 --password secret
"""

import argparse
import csv
import logging
import os
import re
import sys
import unicodedata
import datetime

# Optional: rich (pretty progress bar + table)
try:
    from rich.progress import Progress, BarColumn, TaskProgressColumn, TimeRemainingColumn, TextColumn
    from rich.console import Console
    from rich.table import Table
    RICH = True
except ImportError:
    RICH = False

# MySQL connector (required)
try:
    import mysql.connector
    from mysql.connector import Error as MySQLError
except ImportError:
    print("ERROR: mysql-connector-python is not installed.")
    print("  Run: pip install mysql-connector-python")
    sys.exit(1)


# ─────────────────────────────────────────────────────────
# Logging setup  (always writes to librix-backend/logs/)
# ─────────────────────────────────────────────────────────

LOG_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "librix-backend", "logs")
os.makedirs(LOG_DIR, exist_ok=True)

LOG_FILE = os.path.join(
    LOG_DIR,
    "import_{}.log".format(datetime.datetime.now().strftime("%Y%m%d_%H%M%S"))
)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%H:%M:%S",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
log = logging.getLogger("librix-import")


# ─────────────────────────────────────────────────────────
# CLI
# ─────────────────────────────────────────────────────────

def parse_args():
    p = argparse.ArgumentParser(description="LibriX books_clean.csv importer")
    p.add_argument("--csv",      default="books_clean.csv")
    p.add_argument("--org",      type=int,  default=None)
    p.add_argument("--limit",    type=int,  default=None)
    p.add_argument("--dry-run",  action="store_true")
    p.add_argument("--host",     default=os.getenv("LIBRIX_DB_HOST",     "localhost"))
    p.add_argument("--port",     type=int,  default=int(os.getenv("LIBRIX_DB_PORT",  "3306")))
    p.add_argument("--db",       default=os.getenv("LIBRIX_DB_NAME",     "librix"))
    p.add_argument("--user",     default=os.getenv("LIBRIX_DB_USER",     "root"))
    p.add_argument("--password", default=os.getenv("LIBRIX_DB_PASSWORD", "admin"))
    return p.parse_args()


# ─────────────────────────────────────────────────────────
# Text utilities
# ─────────────────────────────────────────────────────────

def clean_text(value):
    if value is None:
        return ""
    value = str(value).strip()
    value = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]", "", value)
    value = re.sub(r"\s+", " ", value)
    return value


def normalize_key(value):
    value = clean_text(value)
    value = unicodedata.normalize("NFKC", value)
    return value.casefold()


LANGUAGE_MAP = {
    "eng": "English", "en-us": "English", "en-gb": "English", "en": "English",
    "fre": "French",  "fra": "French",
    "spa": "Spanish",
    "ger": "German",  "deu": "German",
    "ita": "Italian",
    "por": "Portuguese",
    "rus": "Russian",
    "jpn": "Japanese",
    "zho": "Chinese", "chi": "Chinese",
    "ara": "Arabic",
    "kor": "Korean",
    "nld": "Dutch",
    "swe": "Swedish",
    "nor": "Norwegian",
    "dan": "Danish",
    "fin": "Finnish",
    "pol": "Polish",
    "tur": "Turkish",
    "hin": "Hindi",
}


def clean_language(value):
    value = clean_text(value)
    if not value:
        return "English"
    return LANGUAGE_MAP.get(value.lower(), value[:50].title())


def split_genres(value):
    value = clean_text(value)
    if not value:
        return []
    seen, genres = set(), []
    for item in value.split(","):
        item = clean_text(item)
        if not item:
            continue
        key = normalize_key(item)
        if key not in seen:
            seen.add(key)
            genres.append(item[:100])
    return genres


def analyse_text(sample):
    """Return (word_count, sentence_count, syllable_count)."""
    if not sample:
        return 50, 3, 75
    words     = re.findall(r"[A-Za-z']+", sample)
    sentences = [s for s in re.split(r"[.!?]+", sample) if s.strip()]
    wc = len(words)
    sc = max(1, len(sentences))
    syl = 0
    for w in words:
        v = re.findall(r"[aeiouy]+", w.lower())
        s = max(1, len(v))
        if w.lower().endswith("e") and len(w) > 2:
            s -= 1
        syl += max(1, s)
    return wc, sc, syl


def estimate_readability(wc, sc, syl):
    """Return (flesch_ease, kincaid_grade, difficulty_level, reading_minutes)."""
    if sc < 1 or wc < 1:
        return 70.0, 7.0, "standard", 300
    asl = wc / sc
    asw = syl / wc if syl else 1.5
    fre = round(max(0.0, min(100.0, 206.835 - 1.015 * asl - 84.6 * asw)), 2)
    fkg = round(max(0.0, 0.39 * asl + 11.8 * asw - 15.59), 2)
    if   fre >= 90: diff = "very_easy"
    elif fre >= 80: diff = "easy"
    elif fre >= 70: diff = "fairly_easy"
    elif fre >= 60: diff = "standard"
    elif fre >= 50: diff = "fairly_difficult"
    elif fre >= 30: diff = "difficult"
    else:           diff = "very_difficult"
    mins = max(1, round(wc / 250))
    return fre, fkg, diff, mins


# ─────────────────────────────────────────────────────────
# DB helpers
# ─────────────────────────────────────────────────────────

def get_connection(args):
    return mysql.connector.connect(
        host=args.host, port=args.port, database=args.db,
        user=args.user, password=args.password,
        charset="utf8mb4", collation="utf8mb4_unicode_ci", autocommit=False,
    )


def ensure_schema(cursor):
    """Idempotently add any missing columns."""
    needed = {
        "books": {
            "average_rating":  "DECIMAL(3,2) NULL",
            "rating_count":    "INT UNSIGNED NOT NULL DEFAULT 0",
            "content":         "LONGTEXT NULL",
            "cover_image":     "VARCHAR(500) NULL",
        },
        "users": {
            "profile_picture_path": "VARCHAR(500) NULL",
        },
    }
    for table, cols in needed.items():
        for col, defn in cols.items():
            cursor.execute("SHOW COLUMNS FROM `{}` LIKE %s".format(table), (col,))
            if not cursor.fetchone():
                cursor.execute("ALTER TABLE `{}` ADD COLUMN `{}` {}".format(table, col, defn))
                log.info("Added column `%s`.`%s`", table, col)


def load_cache(cursor, table, col="name"):
    cursor.execute("SELECT id, `{}` FROM `{}`".format(col, table))
    return {normalize_key(str(v)): rid for rid, v in cursor.fetchall()}


def upsert_lookup(cursor, table, name, cache, col="name", max_len=150):
    key = normalize_key(name)
    if key in cache:
        return cache[key]
    name = name[:max_len]
    try:
        cursor.execute(
            "INSERT INTO `{}` (`{}`) VALUES (%s)".format(table, col), (name,)
        )
        rid = cursor.lastrowid
    except mysql.connector.IntegrityError:
        cursor.execute(
            "SELECT id FROM `{}` WHERE `{}` = %s LIMIT 1".format(table, col), (name,)
        )
        row = cursor.fetchone()
        rid = row[0] if row else None
    if rid:
        cache[key] = rid
    return rid


def book_exists(cursor, title):
    cursor.execute("SELECT 1 FROM books WHERE title = %s LIMIT 1", (title,))
    return cursor.fetchone() is not None


def count_csv_rows(csv_path):
    with open(csv_path, "r", encoding="utf-8-sig", newline="") as f:
        return sum(1 for _ in f) - 1


BATCH = 500


# ─────────────────────────────────────────────────────────
# Core import loop
# ─────────────────────────────────────────────────────────

def run_import(args, conn, cursor, total_rows, progress=None, task=None):
    author_cache   = load_cache(cursor, "authors")
    category_cache = load_cache(cursor, "categories")
    log.info("Authors loaded  : %d", len(author_cache))
    log.info("Categories loaded: %d", len(category_cache))

    inserted = already_exists = skipped = failed = 0
    csv_path = args.csv if os.path.isabs(args.csv) else os.path.join(
        os.path.dirname(os.path.abspath(__file__)), args.csv
    )

    with open(csv_path, "r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)

        for n, row in enumerate(reader, 1):
            if args.limit and n > args.limit:
                break
            if progress and task is not None:
                progress.advance(task)

            try:
                title       = clean_text(row.get("title"))
                author_name = clean_text(row.get("author"))
                genres      = split_genres(row.get("genres"))
                description = clean_text(row.get("description")) or None
                language    = clean_language(row.get("language_code"))
                image_url   = clean_text(row.get("image_url")) or None
                content     = clean_text(row.get("content")) or None

                if not title or not author_name:
                    skipped += 1
                    continue

                if book_exists(cursor, title[:255]):
                    already_exists += 1
                    continue

                try:
                    rating = round(max(0.0, min(5.0, float(clean_text(row.get("rating"))))), 2)
                except (ValueError, TypeError):
                    rating = None

                try:
                    num_ratings = int(float(clean_text(row.get("numRatings"))))
                except (ValueError, TypeError):
                    num_ratings = 0

                author_id   = upsert_lookup(cursor, "authors",    author_name, author_cache,   max_len=150)
                category_id = None
                all_genres  = None

                if genres:
                    category_id = upsert_lookup(cursor, "categories", genres[0], category_cache, max_len=100)
                    all_genres  = ", ".join(genres)[:100]

                # Insert book
                cursor.execute("""
                    INSERT INTO books (
                        org_id, author_id, category_id, publisher_id,
                        title, isbn, description, category, language,
                        publisher, publication_year, total_copies,
                        available_copies, cover_image, average_rating,
                        rating_count, content
                    ) VALUES (
                        %s, %s, %s, NULL,
                        %s, NULL, %s, %s, %s,
                        NULL, NULL, 1,
                        1, %s, %s, %s, %s
                    )
                """, (
                    args.org, author_id, category_id,
                    title[:255], description, all_genres, language,
                    image_url, rating, num_ratings, content,
                ))
                book_id = cursor.lastrowid

                # Insert readability analysis
                sample = content or description or title
                wc, sc, syl = analyse_text(sample)
                fre, fkg, diff, mins = estimate_readability(wc, sc, syl)

                cursor.execute("""
                    INSERT INTO readability_analysis (
                        book_id, sample_text, word_count, sentence_count,
                        syllable_count, flesch_reading_ease,
                        flesch_kincaid_grade, difficulty_level,
                        estimated_reading_minutes
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        flesch_reading_ease       = VALUES(flesch_reading_ease),
                        flesch_kincaid_grade      = VALUES(flesch_kincaid_grade),
                        difficulty_level          = VALUES(difficulty_level),
                        estimated_reading_minutes = VALUES(estimated_reading_minutes)
                """, (book_id, (sample or "")[:2000], wc, sc, syl, fre, fkg, diff, mins))

                inserted += 1
                if inserted % BATCH == 0:
                    conn.commit()
                    log.info("  Progress: %d inserted, %d existed, %d failed",
                             inserted, already_exists, failed)

            except Exception as exc:
                conn.rollback()
                failed += 1
                log.warning("Row %d failed: %s", n, exc)
                # Refresh caches after rollback
                author_cache   = load_cache(cursor, "authors")
                category_cache = load_cache(cursor, "categories")

    conn.commit()
    return inserted, already_exists, skipped, failed, author_cache, category_cache


# ─────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────

def main():
    args = parse_args()

    csv_path = args.csv if os.path.isabs(args.csv) else os.path.join(
        os.path.dirname(os.path.abspath(__file__)), args.csv
    )
    if not os.path.exists(csv_path):
        log.error("CSV not found: %s", csv_path)
        sys.exit(1)

    total_rows = count_csv_rows(csv_path)
    effective  = min(total_rows, args.limit) if args.limit else total_rows

    log.info("=" * 55)
    log.info("  LibriX Book Importer v2.0")
    log.info("=" * 55)
    log.info("CSV       : %s", csv_path)
    log.info("Total CSV : %d rows", total_rows)
    log.info("Importing : %d rows", effective)
    log.info("Org ID    : %s", args.org or "NULL (global / all orgs)")
    log.info("Dry-run   : %s", args.dry_run)
    log.info("Log file  : %s", LOG_FILE)
    log.info("=" * 55)

    # Dry-run mode: no DB needed
    if args.dry_run:
        log.info("DRY-RUN active — no database writes.")
        valid = skipped = 0
        with open(csv_path, "r", encoding="utf-8-sig", newline="") as f:
            for n, row in enumerate(csv.DictReader(f), 1):
                if args.limit and n > args.limit:
                    break
                if clean_text(row.get("title")) and clean_text(row.get("author")):
                    valid += 1
                else:
                    skipped += 1
        log.info("Dry-run done. Valid: %d  Skipped: %d", valid, skipped)
        return

    # Connect to DB
    try:
        conn   = get_connection(args)
        cursor = conn.cursor()
        log.info("Connected to MySQL at %s:%s/%s", args.host, args.port, args.db)
    except MySQLError as exc:
        log.error("Cannot connect to MySQL: %s", exc)
        log.error("Hint: check --host, --user, --password, --db flags.")
        sys.exit(1)

    try:
        ensure_schema(cursor)
        conn.commit()

        if RICH:
            with Progress(
                TextColumn("[bold cyan]{task.description}"),
                BarColumn(bar_width=40),
                TaskProgressColumn(),
                TimeRemainingColumn(),
            ) as progress:
                task = progress.add_task("Importing books...", total=effective)
                ins, exists, skip, fail, ac, cc = run_import(
                    args, conn, cursor, effective, progress, task
                )
        else:
            ins, exists, skip, fail, ac, cc = run_import(
                args, conn, cursor, effective
            )

        # ── Print summary ──────────────────────────────────
        summary = [
            ("Books inserted",     ins),
            ("Already existed",    exists),
            ("Skipped (invalid)",  skip),
            ("Failed (errors)",    fail),
            ("Authors in DB",      len(ac)),
            ("Categories in DB",   len(cc)),
            ("Org ID",             args.org or "NULL (global)"),
            ("Log file",           LOG_FILE),
        ]

        log.info("=" * 55)
        log.info("  IMPORT COMPLETE")
        log.info("=" * 55)
        for label, value in summary:
            log.info("  %-22s : %s", label, value)
        log.info("=" * 55)

        if RICH:
            table = Table(title="LibriX Import Summary", style="bold")
            table.add_column("Metric",  style="cyan",  no_wrap=True)
            table.add_column("Value",   style="green", justify="right")
            for label, value in summary:
                table.add_row(str(label), str(value))
            Console().print(table)

    except MySQLError as exc:
        log.error("MySQL error: %s", exc)
        conn.rollback()
        sys.exit(1)
    finally:
        cursor.close()
        conn.close()
        log.info("Database connection closed.")


if __name__ == "__main__":
    main()
