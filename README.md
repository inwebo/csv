# PHP CSV Reader & Writer
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/inwebo/csv-reader/.github%2Fworkflows%2Flibrary.yml?branch=master&style=flat-square)
![Packagist Version](https://img.shields.io/packagist/v/inwebo/csv-reader?style=flat-square)
![Packagist Downloads](https://img.shields.io/packagist/dd/inwebo/csv-reader?style=flat-square)
![Packagist License](https://img.shields.io/packagist/l/inwebo/csv-reader?style=flat-square)
![PHP Version](https://img.shields.io/packagist/php-v/inwebo/csv-reader?style=flat-square)
![PHPStan Level](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg?style=flat-square)

This library provides two classes — `Inwebo\Csv\Reader` and `Inwebo\Csv\Writer` — for reading and writing CSV files with a low memory footprint. Both extend PHP's `SplFileObject`, making all native file handling methods (like `setCsvControl`) available on each instance.

See the [PHP documentation for SplFileObject](https://www.php.net/manual/en/class.splfileobject.php) for more details.

-----

### Key Features

* **Column Name Mapping**: Automatically maps each line's data to an associative array using the CSV header as keys, making your code more readable and maintainable.
* **Data Normalization**: Apply one or more callable functions to each line to clean and format the data before it's used.
* **Data Filtering**: Use callable functions to validate and filter out rows that don't meet your criteria.
* **Generator-based Iteration**: Process large files efficiently using a `Generator` to iterate over lines without consuming too much memory. Iteration always starts with a `rewind()`.
* **CSV Writing**: Write rows one by one or from any iterable, including Generators, for memory-efficient ETL pipelines.
* **Excel Compatibility**: Optional UTF-8 BOM and configurable line endings (`\r\n`) for correct rendering in Excel on Windows.
* **Inherits `SplFileObject`**: Leverage all the native features and performance benefits of `SplFileObject` for file handling.

-----

### Installation

```shell
  composer req inwebo/csv-reader
```
## Tests

```shell
  composer phpunit
```

## PhpStan

```shell
  composer phpstan
```
> Level 10
-----

### Usage

#### Basic Reading

To get started, simply instantiate the `Reader` class with the path to your CSV file. By default, it assumes the first row contains column names, and data starts at index `1`.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv');

foreach ($reader->rows() as $row) {
    /** @var array{FirstName: string, LastName: string, Gender: string} $row */
    // $row will be an associative array, e.g., ['FirstName' => 'Philippe', 'LastName' => 'Petit', 'Gender' => 'M']
    print_r($row);
}
```

#### Disabling Column Names

If your CSV file does not have a header row, you can disable the column name mapping by setting the `hasHeaders` parameter to `false`. In this case, indices start at `0`.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv', hasHeaders: false);

foreach ($reader->rows() as $row) {
    // $row will be a numeric array, e.g., [0 => 'Philippe', 1 => 'Petit', 2 => 'M']
    print_r($row);
}
```

-----

#### Manual Column Mapping

For files without a header, you can manually define column names using the `setHeader()` method. This allows you to treat the data as an associative array even without a header row. Indices start at `0`.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv', hasHeaders: false);

$reader
    ->setHeader(0, 'firstname')
    ->setHeader(1, 'lastname')
    ->setHeader(2, 'gender');

foreach ($reader->rows() as $row) {
    /** @var array{firstname: string, lastname: string, gender: string} $row */
    // $row will be an associative array, e.g., ['firstname' => 'Philippe', 'lastname' => 'Petit', 'gender' => 'M']
    print_r($row);
}
```

-----

### Advanced Usage: Normalizers and Filters

You can add multiple normalizers and filters to your `Reader` instance. They are executed sequentially in the order they are added (FIFO).

#### Normalizers

Normalizers are used to modify the data. The callback receives the line array by reference, allowing you to directly alter its values. They are executed sequentially in the order they are added (FIFO).

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv');

// Add a normalizer to handle missing gender data
$reader->pushNormalizer(function (array &$row): void {
    /** @var array{Gender: string} $row */
    if (empty($row['Gender'])) {
        $row['Gender'] = 'U';
    }
});

// Add another normalizer to format the gender column
$reader->pushNormalizer(function (array &$row): void{
    /** @var array{Gender: string} $row */
    $gender = strtolower($row['Gender']);
    if (str_starts_with($gender, 'm')) {
        $row['Gender'] = 'M';
    } elseif (str_starts_with($gender, 'f')) {
        $row['Gender'] = 'F';
    }
});

// Add a normalizer to ensure Salary is an integer
$reader->pushNormalizer(function (array &$row): void {
    /** @var array{Salary: string|int|null} $row */
    $row['Salary'] = is_null($row['Salary']) ? 0 : (int) $row['Salary'];
});
```

#### Filters

Filters are used to validate and exclude entire rows. If a filter returns `false`, the line will be skipped and will not be yielded by the generator.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv');

// Add a filter to only include rows where Salary is greater than 80 000 €
$reader->pushFilter(function (array $row): bool {
    /** @var array{Salary: string|int|null} $row */
    return isset($row['Salary']) && (int) $row['Salary'] > 80000;
});

// Add another filter to only include users older than 25
$reader->pushFilter(function (array $row): bool {
    /** @var array{Age: string|int|null} $row */
    return isset($row['Age']) && (int) $row['Age'] > 25;
});
```

With both normalizers and filters in place, the processing loop becomes a clean, declarative statement of what you want to achieve.

```php
foreach ($reader->rows() as $row) {
    // This line has passed all your checks and is ready to be used
    print_r($row);
}
```

#### Reading a Specific Range

You can also read a specific range of rows using the `rows()` method with `from` and `to` parameters. Both parameters must be provided together or both omitted.

> [!IMPORTANT]
> When `hasHeaders` is `true` (default), the first data row is at index `1`. When `false`, it starts at `0`.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv');

// Read rows from 10 to 20
foreach ($reader->rows(from: 10, to: 20) as $row) {
    print_r($row);
}
```

#### Reading a Specific Row

The `rowAt()` method allows you to retrieve a specific row by its index. If the row contains missing columns compared to the header, they will be returned as `null`.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('path/to/your/file.csv');

// Retrieve the 5th row
$row = $reader->rowAt(5);
print_r($row);
```

-----

## Writer

### Basic Writing

Instantiate `Writer` with the path to the output file. The default mode is `'w'` (truncate or create). Use `'a'` to append to an existing file.

```php
use Inwebo\Csv\Writer;

$writer = new Writer('path/to/output.csv');

$writer->setHeaders(['FirstName', 'LastName', 'Email']);
$writer->row(['Philippe', 'Petit', 'philippe@example.com']);
$writer->row(['Marie', 'Curie', 'marie@example.com']);
```

All configuration methods return `static` for fluent chaining:

```php
$writer
    ->setHeader(['FirstName', 'LastName'])
    ->row(['Philippe', 'Petit'])
    ->row(['Marie', 'Curie'])
;
```

### Writing Multiple Rows

`rows()` accepts any `iterable` — arrays or `Generator` objects:

```php
$data = [
    ['Philippe', 'Petit'],
    ['Marie', 'Curie'],
];

$writer->setHeader(['FirstName', 'LastName'])->rows($data);
```

### Excel Compatibility

Enable the UTF-8 BOM to ensure correct character encoding when opening the file in Excel on Windows. Use `\r\n` as the line ending for RFC 4180 compliance.

```php
$writer = new Writer('path/to/output.csv', bom: true);
$writer->setLineEnding("\r\n");
```

The BOM is written automatically before the first row (header or data).

### Custom Delimiter

CSV control (delimiter, enclosure, escape) is delegated to the inherited `setCsvControl()` method:

```php
$writer = new Writer('path/to/output.csv');
$writer->setCsvControl(';'); // use semicolon as delimiter (common in France/Germany)
$writer->setHeader(['Prénom', 'Nom'])->row(['Philippe', 'Petit']);
```

-----

### Filters and Normalizers

Filters and normalizers on the `Writer` follow the same FIFO pipeline as on the `Reader`. For each row, filters run first — if any returns `false` the row is skipped entirely — then normalizers transform the data before it is written.

Filters and normalizers **do not apply to the header row** written by `setHeader()`.

#### Validating column count

A common use case is rejecting malformed rows whose column count does not match the header before writing them:

```php
use Inwebo\Csv\Writer;

$writer = new Writer('output.csv');
$headers = ['Id', 'FirstName', 'LastName', 'Email'];

$writer->setHeaders($headers);

$writer->pushFilter(function (array $row) use ($headers): bool {
    return count($row) === count($headers);
});

$writer->rows([
    ['1', 'Alice', 'Dupont', 'alice@example.com'],  // written
    ['2', 'Bob'],                                    // skipped — only 2 columns
    ['3', 'Charlie', 'Martin', 'charlie@example.com'], // written
]);
```

#### Normalizing data before writing

Normalizers receive the row array by reference, allowing direct modification:

```php
use Inwebo\Csv\Writer;

$writer = new Writer('output.csv');

// Trim whitespace and normalize casing on name columns
$writer->pushNormalizer(function (array &$row): void {
    $row['FirstName'] = mb_convert_case(trim($row['FirstName']), MB_CASE_TITLE, 'UTF-8');
    $row['LastName']  = mb_convert_case(trim($row['LastName']),  MB_CASE_TITLE, 'UTF-8');
});

// Format phone numbers
$writer->pushNormalizer(function (array &$row): void {
    $row['Phone'] = preg_replace('/\D/', '', $row['Phone'] ?? '');
});

$writer->setHeaders(['FirstName', 'LastName', 'Phone']);
$writer->rows($data);
```

#### Combining filters and normalizers

Filters and normalizers can be combined freely. The pipeline order is always: **filter → normalize → write**.

```php
use Inwebo\Csv\Writer;

$headers = ['Id', 'FirstName', 'LastName', 'Email', 'Salary'];
$writer  = new Writer('output.csv');
$writer->setHeaders($headers);

// Skip rows with wrong column count or invalid email
$writer->pushFilter(fn(array $row) use ($headers): bool => count($row) === count($headers));
$writer->pushFilter(fn(array $row): bool => filter_var($row['Email'], FILTER_VALIDATE_EMAIL) !== false);

// Normalize names and cast salary to int
$writer->pushNormalizer(function (array &$row): void {
    $row['FirstName'] = mb_convert_case(trim($row['FirstName']), MB_CASE_TITLE, 'UTF-8');
    $row['LastName']  = mb_convert_case(trim($row['LastName']),  MB_CASE_TITLE, 'UTF-8');
    $row['Salary']    = (string) (int) $row['Salary'];
});

$writer->rows($data);
```

-----

### ETL: Reader to Writer

Because `Writer::rows()` accepts any `iterable`, you can pipe `Reader::rows()` directly into it without buffering the entire file in memory:

```php
use Inwebo\Csv\Reader;
use Inwebo\Csv\Writer;

$reader = new Reader('input.csv');
$writer = new Writer('output.csv');

$writer->setHeaders($reader->getHeaders());
$writer->rows($reader->rows());
```

Filters and normalizers applied to the `Reader` are evaluated lazily during iteration, so the pipeline processes one row at a time regardless of file size.

-----

### Realistic Scenario: Customer Migration

This scenario reads a legacy customer CSV (`tests/Fixtures/example.csv`), cleans and filters the data, then writes two separate output files — one per segment — without ever loading the full file into memory.

We need to:
* Reject rows with a wrong column count.
* Clean up first and last names (trimming, casing).
* Format phone numbers.
* Formalize genders to 'M' or 'F'.
* Filter for valid email addresses.
* Output 1: Women with a salary < 10,000 → `women.csv`
* Output 2: Men with a salary > 22,500 → `men.csv`

```php
use Inwebo\Csv\Reader;
use Inwebo\Csv\Writer;

$reader = new Reader('tests/Fixtures/example.csv');

// Shared normalizers applied by the Reader on every row
$reader->pushNormalizer(function (array &$row): void {
    $row['FirstName'] = mb_convert_case(trim($row['FirstName']), MB_CASE_TITLE, 'UTF-8');
    $row['LastName']  = mb_convert_case(trim($row['LastName']),  MB_CASE_TITLE, 'UTF-8');
});

$reader->pushNormalizer(function (array &$row): void {
    if (!empty($row['Phone'])) {
        $row['Phone'] = str_replace(['.', ' ', '-', '+33'], '', $row['Phone']);
        if (strlen($row['Phone']) === 9) {
            $row['Phone'] = '0' . $row['Phone'];
        }
    }
});

$reader->pushNormalizer(function (array &$row): void {
    $gender = strtoupper(trim($row['Gender']));
    $row['Gender'] = match(true) {
        in_array($gender, ['M', 'MALE'])   => 'M',
        in_array($gender, ['F', 'FEMALE']) => 'F',
        default                            => 'U',
    };
});

// Shared Writer setup (Excel-compatible: BOM + semicolon delimiter)
$headers = $reader->getHeaders();

$initWriter = static function (string $filename) use ($headers): Writer {
    $writer = new Writer($filename, bom: true);
    $writer->setLineEnding("\r\n");
    $writer->setCsvControl(';');
    $writer->pushFilter(fn(array $row) use ($headers): bool => count($row) === count($headers));
    $writer->pushFilter(fn(array $row): bool => filter_var($row['Email'], FILTER_VALIDATE_EMAIL) !== false);
    $writer->setHeaders($headers);

    return $writer;
};

// Output 1: Women with salary < 10,000
$womenWriter = $initWriter('women.csv');
$womenWriter->pushFilter(fn(array $row): bool => $row['Gender'] === 'F' && (int) $row['Salary'] < 10000);
$womenWriter->rows($reader->rows());

// Output 2: Men with salary > 22,500
$menWriter = $initWriter('men.csv');
$menWriter->pushFilter(fn(array $row): bool => $row['Gender'] === 'M' && (int) $row['Salary'] > 22500);
$menWriter->rows($reader->rows());
```