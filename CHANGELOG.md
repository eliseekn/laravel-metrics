# Changelog

All notable changes to `laravel-metrics` will be documented in this file

## 2.8.0

- Add PostgreSQL support

## 2.7.4

- Add "from" period to set custom startDate end use the current date as endDate for between period

## 2.7.3

- Fix null exception on get metrics data

## 2.7.0

- Replace fillEmptyData by fillMissingData
- Set fillMissingData as global method
- Add groupBy methods for between period
- Fix some bugs

## 2.6.1

- Add Combined periods and aggregates methods

## 2.6

- Add 'fillEmptyDates' method to fill data for empty dates
- Fix get trends from 'between' method when using custom label

## 2.4.1

- Fix undefined array key 'data' exception when trends are empty

## 2.4.0

- Add HasMetrics trait for models

## 2.3.0

- Add forDay and forMonth methods

## 2.1.0

- Add custom label column definition
- Move periods and aggregates constants to enums

## 2.0.0

- Upgrade to PHP version to 8.1
- Add SQLite support
- Update whole code structure
- Remove static method
- Add eloquent query builder

## 1.0.5 - 2022-01-04

- Fix parameters types

## 1.0.4 - 2022-01-04

- Add demo project link

## 1.0.3 - 2021-12-30

- Fix getTrends() returns data
- Fix custom date range result

## 1.0.2 - 2021-12-30

- Add custom date range

## 1.0.1 - 2021-09-25

- Add Carbon package
- Remove unnecessary files and folder

## 1.0.0 - 2021-09-25

- Initial release
