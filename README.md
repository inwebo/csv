# PHP CSV Reader
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/inwebo/csv-reader/.github%2Fworkflows%2Flibrary.yml?branch=master&style=flat-square)
![Packagist Version](https://img.shields.io/packagist/v/inwebo/csv-reader?style=flat-square)
![Packagist Downloads](https://img.shields.io/packagist/dd/inwebo/csv-reader?style=flat-square)
![Packagist License](https://img.shields.io/packagist/l/inwebo/csv-reader?style=flat-square)
![PHP Version](https://img.shields.io/packagist/php-v/inwebo/csv-reader?style=flat-square)
![PHPStan Level](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg?style=flat-square)

This PHP class, `Inwebo\Csv\Reader`, provides a simple, really fast and low memory footprint way to read and process CSV files. Built as an extension of PHP's `SplFileObject`, it offers advanced features like **column name mapping**, **data filtering**, and **normalization** to streamline your CSV processing tasks.

Since it extends `\SplFileObject`, all methods to configure CSV reading (like `setCsvControl`) are available. See the [PHP documentation for SplFileObject](https://www.php.net/manual/en/class.splfileobject.php) for more details.

-----

### Key Features

* **Column Name Mapping**: Automatically maps each line's data to an associative array using the CSV header as keys, making your code more readable and maintainable.
* **Data Normalization**: Apply one or more callable functions to each line to clean and format the data before it's used.
* **Data Filtering**: Use callable functions to validate and filter out rows that don't meet your criteria.
* **Generator-based Iteration**: Process large files efficiently using a `Generator` to iterate over lines without consuming too much memory. Iteration always starts with a `rewind()`.
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

// Add a filter to only include rows where Salary is greater than 80000
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

### Realistic Scenario: Customer Migration

This scenario demonstrates a realistic use case: migrating a customer database from an old CSV file (`tests/Fixtures/example.csv`) to a new system.

We need to:
* Clean up first and last names (trimming, casing).
* Format phone numbers.
* Formalize genders to 'M' or 'F'.
* Filter for valid email addresses.
* Example 1: Only women with a salary < 10,000.
* Example 2: Only men with a salary > 22,500.

```php
use Inwebo\Csv\Reader;

$reader = new Reader('tests/Fixtures/example.csv');

// 1. Clean names and surnames
$reader->pushNormalizer(function (array &$row): void {
    $row['FirstName'] = mb_convert_case(trim($row['FirstName']), MB_CASE_TITLE, "UTF-8");
    $row['LastName'] = mb_convert_case(trim($row['LastName']), MB_CASE_TITLE, "UTF-8");
});

// 2. Format phone numbers (basic example)
$reader->pushNormalizer(function (array &$row): void {
    if (!empty($row['Phone'])) {
        $row['Phone'] = str_replace(['.', ' ', '-', '+33'], '', $row['Phone']);
        if (strlen($row['Phone']) === 9) {
            $row['Phone'] = '0' . $row['Phone'];
        }
    }
});

// 3. Formalize genders (M/F)
$reader->pushNormalizer(function (array &$row): void {
    $gender = strtoupper(trim($row['Gender']));
    if (in_array($gender, ['M', 'MALE'])) {
        $row['Gender'] = 'M';
    } elseif (in_array($gender, ['F', 'FEMALE'])) {
        $row['Gender'] = 'F';
    } else {
        $row['Gender'] = 'U'; // Unknown
    }
});

// 4. Filter for valid emails
$reader->pushFilter(function (array $row): bool {
    return filter_var($row['Email'], FILTER_VALIDATE_EMAIL) !== false;
});

// Example 1: Women with salary < 10,000
$reader->pushFilter(function (array $row): bool {
    return $row['Gender'] === 'F' && (int)$row['Salary'] < 10000;
});

foreach ($reader->rows() as $row) {
    // Process matching women
}

// Example 2: Men with salary > 22,500
$reader->clearFilters(); // Clear previous filters

// Re-apply common filters
$reader->pushFilter(function (array $row): bool {
    return filter_var($row['Email'], FILTER_VALIDATE_EMAIL) !== false;
});

$reader->pushFilter(function (array $row): bool {
    return $row['Gender'] === 'M' && (int)$row['Salary'] > 22500;
});

foreach ($reader->rows() as $row) {
    // Process matching men
}
```