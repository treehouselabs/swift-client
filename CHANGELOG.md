## v1.0.0

### Changes
* Upgraded Guzzle to version 5
* Upgraded the Keystone client to version 2
* Added tests and documentation

### Breaks
* `SwiftDriver` now uses Guzzle's `ClientInterface`, rather than the Keystone
  client
* Renamed `SwiftDriver::getPublicUrl => SwiftDriver::getBaseUrl()`
* Response codes for store operations are now checked. You could get exceptions
  where previously methods would return `null` or `false`. All exceptions
  thrown are of type `TreeHouse\Swift\Exception\SwiftException`.
