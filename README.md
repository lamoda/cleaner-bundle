# Lamoda cleaner bundle

Symfony Bundle for lamoda/cleaner library.


## Installation

1. Install the Bundle with composer:
```console
$ composer require lamoda/cleaner-bundle
```

2. Enable the Bundle:
```php
<?php
// config/bundles.php

return [
    // ...
    Lamoda\CleanerBundle\LamodaCleanerBundle::class => ['all' => true],
    // ...
];
```

3. Configure cleaners for your project:
```yaml
lamoda_cleaner:
    db:
        queue:
            transactional: false  # optional, default true
            query: DELETE FROM queue WHERE created_at < NOW() - (:interval || ' days')::interval
            parameters:
                interval: 30
            types:  # optional, if required special type handling
                - integer

        # you can use multiple queries at one command
        multi_tables:
            class: Lamoda\Cleaner\DB\DoctrineDBALCleaner
            transactional: true
            queries:
                - query: DELETE FROM table_a WHERE created_at < NOW() - (:interval || ' days')::interval
                  parameters:
                      interval: 30
                - query: DELETE FROM table_b WHERE created_at < NOW() - (:interval || ' days')::interval
                  parameters:
                      interval: 30
```

You can also add your own storage cleaners.
To do this you have to implement `Lamoda\Cleaner\CleanerInterface` and register your cleaner with tag:
```yaml
services:
    custom_cleaner:
        class: My\Custom\Implementation
        tags:
            - { name: 'lamoda_cleaner.db', alias: 'custom' }
```


## Usage

Bundle adds command to run cleaners.

Run all cleaners in DB:
```bash
./bin/console cleaner:clear db
```

or only one of them:
```bash
./bin/console cleaner:clear db queue
```
