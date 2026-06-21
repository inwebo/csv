# CHANGELOG

## 2.0.0

### Added
* `Writer` — nouvelle classe symétrique à `Reader` pour l'écriture de CSV
* `ReaderInterface` et `WriterInterface` — contrats publics pour `Reader` et `Writer`
* `HasFiltersQueueInterface` / `HasFiltersQueueTrait` — filtre extraits en interface + trait réutilisable
* `HasNormalizersQueueInterface` / `HasNormalizersQueueTrait` — idem pour les normalizers
* Hiérarchie d'exceptions : `CsvException` (base) → `InvalidRangeException`, `BadArgumentException`, `WriteException`
* `Reader::setFlags()` — valide que les flags `READ_CSV|SKIP_EMPTY|DROP_NEW_LINE|READ_AHEAD` sont préservés
* `Reader::$headersIsList` et `Reader::$headerCount` — valeurs invariantes calculées une seule fois au constructeur

### Changed
* **[BREAKING]** `FiltersQueue` déplacé : `Inwebo\Csv\Model\FiltersQueue` → `Inwebo\Csv\Model\Filters\FiltersQueue`
* **[BREAKING]** `NormalizersQueue` déplacé : `Inwebo\Csv\Model\NormalizersQueue` → `Inwebo\Csv\Model\Normalizers\NormalizersQueue`
* **[BREAKING]** Constructeur `Reader` : suppression des paramètres `$mode` et `$context` (le mode `'r'` est fixe)
* **[BREAKING]** `rows()` lève désormais `InvalidRangeException` au lieu de `\InvalidArgumentException`
* `Reader` implémente `HasFiltersQueueInterface`, `HasNormalizersQueueInterface`, `ReaderInterface` via traits
* `private const REQUIRED_FLAGS` typé explicitement en `int` (PHP 8.3+)
* Types PHPDoc des callables : `callable(array<int|string, ?string>)` → `callable(array<int|string, mixed>)` (correction de contravariance)

### Fixed
* `call_user_func` / `call_user_func_array` remplacés par l'invocation directe dans `FiltersQueue` et `NormalizersQueue` — gain mesuré de **−43 %** sur `filter()` et **−36 %** sur `normalize()` (100 000 lignes, Callgrind)
* `setHeadersRow()` : `array_is_list($headers)` et `count($headers)` mis en cache au constructeur — supprime deux appels par ligne
* `FiltersQueue::isNotEmpty()` supprimé (redondant avec `isEmpty()`)

### Performance
* Lecture bornée (`rows($from, $to)`) : un seul `seek()` au début au lieu de `seek()` à chaque ligne — complexité O(N) au lieu de O(N²)
  * `benchLargeFile` medium : **94 260 ms → 25 ms** (×3 770)
  * `benchFiltering` large : **−16 %**, `benchNormalization` medium : **−18 %**

## 1.1.1

### Added
* Configuration de Dependabot pour les mises à jour automatiques
* Workflow GitHub Actions pour l'auto-merge de Dependabot
* Workflow GitHub Actions pour le taggage automatique et la mise à jour du CHANGELOG

### Changed
* Mise à jour du README.md avec plus de détails et d'exemples
* Améliorations mineures
* Update back dependencies
* Simplification Reader::normalize(), Reader::filter()

## 1.1.0

### Added
* `ClearableInterface` — contrat commun pour vider une queue
* `FiltersQueue::clear()` et `NormalizersQueue::clear()` implémentent `ClearableInterface`
* `validateInput()` — validation de `$from` : doit être `>= 1` avec en-têtes, `>= 0` sans
* Fixture `tests/Fixtures/malformed.csv` pour tester les CSV mal formés

### Fixed
* Boucle infinie dans `rows()` lors de l'itération sans bornes sur un fichier sans en-têtes (`hasHeaders: false`)
* `rowAt()` : `array_combine` remplacé par `setHeadersRow()` — les colonnes manquantes retournent `null` au lieu de lever un `ValueError`
* `rows()` effectue un `rewind()` au **début** de chaque appel (au lieu de la fin) : comportement prévisible lors d'appels successifs

### Changed
* `clearNormalizers()` et `clearFilters()` délèguent désormais à `clear()` au lieu de recréer un objet
* `getRelativeOffset()` : `--$offset` remplacé par `$offset - 1` (sans effet de bord)
* Variables `$isEmpty` renommées `$hasItems` dans `normalize()` et `filter()`
* `$isValid &= ...` remplacé par `$isValid = $isValid && ...` dans `filter()`
* Namespace des tests : `Inwebo\CSV\Reader\Tests\` → `Inwebo\Csv\Tests\`
* `composer.json` : champ `version` supprimé (géré par les tags Git)
* CI : `--no-coverage` ajouté à l'étape PHPUnit ; workflow renommé `PHP Library`

### Tests
* Ajout de `ReadWithoutHeaderTest::testRows()` — couvre le cas de la boucle infinie
* Ajout de `InstantiateTest::testMalformedCsvRow()` — couvre le cas `array_combine`
* Ajout de `ReaderExceptionTest::testExceptionFromZeroWithHeaders()` et `testExceptionFromNegativeWithoutHeaders()`
* Ajout de `FiltersQueueTest::testClear()` et `NormalizersQueueTest::testClear()`
* `NormalizersQueueTest::testFilter()` renommé `testNormalize()`

## 1.0.2
* Update back dependencies

## 1.0.1
* Update back dependencies

## 1.0.0
* Bonjour le monde