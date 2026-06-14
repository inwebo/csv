# CHANGELOG

## 1.1.1

### Added
* Configuration de Dependabot pour les mises à jour automatiques
* Workflow GitHub Actions pour l'auto-merge de Dependabot
* Workflow GitHub Actions pour le taggage automatique et la mise à jour du CHANGELOG

### Changed
* Mise à jour du README.md avec plus de détails et d'exemples
* Améliorations mineures
* Update back dependencies

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