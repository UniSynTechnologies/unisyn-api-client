# unisyn-api-client-php

Ensure your hosting environment has the php curl and php xml extensions installed and enabled

The required initialize.php file is not included here and must be separately created to initialize the api client. 
This was done to prevent constant conflicting changes while flipping between site ids in development as well as having keys in repo.

The file contents are simply:
```php
<?php
define('UNISYN_API_VERSION', 'v1'); // the api version such as v1 or v2 or v3 ...etc
define('UNISYN_API_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxx'); // the api key for the app
define('UNISYN_API_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxx'); // the api secret for the app
```

Place the initialize.php file in the root of the api client's directory
