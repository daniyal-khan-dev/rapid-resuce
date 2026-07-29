<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v2 Keys
    |--------------------------------------------------------------------------
    |
    | Set RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY in your .env file.
    | Obtain keys from: https://www.google.com/recaptcha/admin/create
    | Choose "reCAPTCHA v2 — I'm not a robot Checkbox".
    |
    | The default values below are Google's official test keys.
    | They always pass verification but display a "testing purposes only"
    | banner. Replace them with your real keys in production.
    |
    */

    'site_key'   => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

];
