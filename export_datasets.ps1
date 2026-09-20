$ErrorActionPreference = "Stop"

$OutputDir = "c:\Users\moham\Desktop\librix\librix-frontend\assets\data"
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$CsvPath = "c:\Users\moham\Desktop\librix\books_clean.csv"
$reader = New-Object System.IO.StreamReader($CsvPath, [System.Text.Encoding]::UTF8)

# Read header
$header = $reader.ReadLine()

$books = [System.Collections.Generic.List[PSObject]]::new()
$categoriesMap = [System.Collections.Generic.Dictionary[string, int]]::new([System.StringComparer]::OrdinalIgnoreCase)
$authorsMap = [System.Collections.Generic.Dictionary[string, int]]::new([System.StringComparer]::OrdinalIgnoreCase)
$readabilityMap = [System.Collections.Generic.Dictionary[string, PSObject]]::new()

$csvRegex = New-Object System.Text.RegularExpressions.Regex('(?!\s*$)\s*(?:\"((?:[^\"]|\"\")*)\"|([^,]*))\s*(?:,|$)', [System.Text.RegularExpressions.RegexOptions]::Compiled)

$count = 0
$maxBooks = 600

while (-not $reader.EndOfStream -and $count -lt $maxBooks) {
    $line = $reader.ReadLine()
    if ([string]::IsNullOrWhiteSpace($line)) { continue }

    $matches = $csvRegex.Matches($line)
    if ($matches.Count -lt 9) { continue }

    $cols = [System.Collections.Generic.List[string]]::new()
    for ($i = 0; $i -lt $matches.Count - 1; $i++) {
        $m = $matches[$i]
        if ($m.Groups[1].Success) {
            $cols.Add($m.Groups[1].Value.Replace('""', '"'))
        } else {
            $cols.Add($m.Groups[2].Value)
        }
    }
    if ($cols.Count -lt 8) { continue }

    $count++
    $id = $count
    $title = $cols[1].Trim()
    if ([string]::IsNullOrEmpty($title)) { $title = "Untitled Classic Book #$id" }
    $author = if ($cols.Count -gt 2 -and $cols[2]) { $cols[2].Trim() } else { "Unknown Author" }

    $ratingVal = 4.30
    if ($cols.Count -gt 3 -and $cols[3]) {
        [double]::TryParse($cols[3], [ref]$ratingVal) | Out-Null
    }
    $numRatings = 1500
    if ($cols.Count -gt 4 -and $cols[4]) {
        [int]::TryParse($cols[4], [ref]$numRatings) | Out-Null
    }

    $genres = if ($cols.Count -gt 5 -and $cols[5]) { $cols[5].Trim() } else { "Fiction" }
    $firstGenre = "Fiction"
    if ($genres.Contains(",")) {
        $firstGenre = $genres.Split(",")[0].Trim()
    } elseif ($genres) {
        $firstGenre = $genres
    }
    $primaryCategory = (Get-Culture).TextInfo.ToTitleCase($firstGenre.ToLower())
    if ([string]::IsNullOrWhiteSpace($primaryCategory)) { $primaryCategory = "Classic Literature" }

    if (-not $categoriesMap.ContainsKey($primaryCategory)) {
        $categoriesMap[$primaryCategory] = 0
    }
    $categoriesMap[$primaryCategory]++

    $firstAuthor = $author
    if ($author.Contains(",")) {
        $firstAuthor = $author.Split(",")[0].Trim()
    }
    if (-not $authorsMap.ContainsKey($firstAuthor)) {
        $authorsMap[$firstAuthor] = 0
    }
    $authorsMap[$firstAuthor]++

    $lang = if ($cols.Count -gt 7 -and $cols[7]) { $cols[7].Trim() } else { "English" }
    if ($lang -eq "eng" -or $lang -eq "en-US") { $lang = "English" }

    $cover = if ($cols.Count -gt 8 -and $cols[8]) { $cols[8].Trim() } else { "" }
    $desc = if ($cols.Count -gt 9 -and $cols[9]) { $cols[9].Trim() } else { "" }
    if ([string]::IsNullOrWhiteSpace($desc)) {
        $desc = "An acclaimed work of literature catalogued in the LibriX digital library collection."
    }

    $totalCopies = ($id % 7) + 3
    $availCopies = [Math]::Max(1, $totalCopies - ($id % 3))
    $pubYear = 1985 + ($id % 39)

    $wordCount = 45000 + (($id * 37) % 55000)
    $sentenceCount = [int]($wordCount / 15)
    $syllableCount = [int]($wordCount * 1.45)
    $ease = [Math]::Round(206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllableCount / $wordCount)), 1)
    if ($ease -lt 30.0) { $ease = 58.5 }
    if ($ease -gt 90.0) { $ease = 76.2 }
    $grade = [Math]::Round((0.39 * ($wordCount / $sentenceCount)) + (11.8 * ($syllableCount / $wordCount)) - 15.59, 1)
    if ($grade -lt 4.0) { $grade = 6.8 }
    if ($grade -gt 14.0) { $grade = 8.4 }

    $diff = if ($ease -ge 80) { 'easy' } elseif ($ease -ge 65) { 'fairly_easy' } elseif ($ease -ge 50) { 'standard' } elseif ($ease -ge 35) { 'fairly_difficult' } else { 'difficult' }
    $estMinutes = [int]($wordCount / 220)

    $bookObj = [PSCustomObject]@{
        id = $id
        org_id = (($id % 4) + 1)
        author_id = $id
        author_name = $author
        category_id = (($id % 10) + 1)
        category = $primaryCategory
        publisher_id = (($id % 5) + 1)
        publisher = 'LibriX Academic Press'
        title = $title
        isbn = "978" + (1000000000 + $id * 23).ToString().Substring(0, 10)
        description = $desc
        language = $lang
        publication_year = $pubYear
        total_copies = $totalCopies
        available_copies = $availCopies
        cover_image = $cover
        average_rating = [Math]::Round($ratingVal, 2)
        rating_count = $numRatings
        created_at = "2026-08-15 10:00:00"
    }
    $books.Add($bookObj)

    $readabilityMap[$id.ToString()] = [PSCustomObject]@{
        id = $id
        book_id = $id
        book_title = $title
        flesch_reading_ease = $ease
        flesch_kincaid_grade = $grade
        difficulty_level = $diff
        estimated_reading_minutes = $estMinutes
        word_count = $wordCount
        sentence_count = $sentenceCount
        syllable_count = $syllableCount
    }
}
$reader.Close()

Write-Host "Processed $count books from books_clean.csv"

# Write Books JSON
$booksJson = $books | ConvertTo-Json -Depth 5 -Compress
[System.IO.File]::WriteAllText("$OutputDir\dataset_books.json", $booksJson, [System.Text.Encoding]::UTF8)

# Write Readability JSON
$readabilityJson = $readabilityMap | ConvertTo-Json -Depth 5 -Compress
[System.IO.File]::WriteAllText("$OutputDir\dataset_readability.json", $readabilityJson, [System.Text.Encoding]::UTF8)

# Write Categories JSON
$catList = [System.Collections.Generic.List[PSObject]]::new()
$catIdx = 1
foreach ($k in $categoriesMap.Keys) {
    $catList.Add([PSCustomObject]@{
        id = $catIdx
        name = $k
        description = "Collection of $k works in the LibriX archives"
        total_books = $categoriesMap[$k]
    })
    $catIdx++
}
$catJson = $catList | ConvertTo-Json -Depth 5 -Compress
[System.IO.File]::WriteAllText("$OutputDir\dataset_categories.json", $catJson, [System.Text.Encoding]::UTF8)

# Write Authors JSON
$authList = [System.Collections.Generic.List[PSObject]]::new()
$authIdx = 1
foreach ($a in $authorsMap.Keys) {
    $authList.Add([PSCustomObject]@{
        id = $authIdx
        name = $a
        biography = "Author with works preserved in the LibriX catalogue."
        total_books = $authorsMap[$a]
    })
    $authIdx++
    if ($authIdx -gt 150) { break }
}
$authJson = $authList | ConvertTo-Json -Depth 5 -Compress
[System.IO.File]::WriteAllText("$OutputDir\dataset_authors.json", $authJson, [System.Text.Encoding]::UTF8)

Write-Host "Export completed successfully!"
