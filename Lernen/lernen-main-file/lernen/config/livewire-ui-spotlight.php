<?php

use App\Spotlight\FavouriteTutors;
use App\Spotlight\Logout;
use App\Spotlight\Messenger;
use App\Spotlight\ManageSessions;
use App\Spotlight\MyBookings;
use App\Spotlight\Search;
use App\Spotlight\PayoutsHistory;
use App\Spotlight\StudentInvoices;
use App\Spotlight\TutorInvoices;
use App\Spotlight\TutorDisputes;
use App\Spotlight\StudentDisputes;

return [

    /*
    |--------------------------------------------------------------------------
    | Shortcuts
    |--------------------------------------------------------------------------
    |
    | Define which shortcuts will activate Spotlight CTRL / CMD + key
    | The default is CTRL/CMD + K or CTRL/CMD + /
    |
    */

    'shortcuts' => [
        'k',
        'slash',
    ],

    /*
    |--------------------------------------------------------------------------
    | Commands
    |--------------------------------------------------------------------------
    |
    | Define which commands you want to make available in Spotlight.
    | Alternatively, you can also register commands in your AppServiceProvider
    | with the Spotlight::registerCommand(Logout::class); method.
    |
    */

    'commands' => [
       Search::class,
       MyBookings::class,
       ManageSessions::class,
       Messenger::class,
       PayoutsHistory::class,
       TutorInvoices::class,
       StudentInvoices::class,
       StudentDisputes::class,
       TutorDisputes::class,
       FavouriteTutors::class,
       Logout::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Include CSS
    |--------------------------------------------------------------------------
    |
    | Spotlight uses TailwindCSS, if you don't use TailwindCSS you will need
    | to set this parameter to true. This includes the modern-normalize css.
    |
    */
    'include_css' => true,


    /*
    |--------------------------------------------------------------------------
    | Include JS
    |--------------------------------------------------------------------------
    |
    | Spotlight will inject the required Javascript in your blade template.
    | If you want to bundle the required Javascript you can set this to false,
    | call 'npm install fuse.js' or 'yarn add fuse.js',
    | then add `require('vendor/wire-elements/spotlight/resources/js/spotlight');`
    | to your script bundler like webpack.
    |
    */
    'include_js' => true,

    /*
    |--------------------------------------------------------------------------
    | Show results without input
    |--------------------------------------------------------------------------
    |
    | Whether to show command search results without
    | having to type anything in the search input.
    |
    */
    'show_results_without_input' => true,

];
