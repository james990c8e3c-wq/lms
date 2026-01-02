<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LivewireUI\Spotlight\Spotlight;
use Nwidart\Modules\Facades\Module;

class SpotlightServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Spotlight::registerCommandIf(Module::has('Courses') && Module::isEnabled('Courses') && class_exists(\Modules\Courses\Spotlight\ManageCourses::class), \Modules\Courses\Spotlight\ManageCourses::class);
        Spotlight::registerCommandIf(Module::has('Courses') && Module::isEnabled('Courses') && class_exists(\Modules\Courses\Spotlight\MyLearning::class), \Modules\Courses\Spotlight\MyLearning::class);
        Spotlight::registerCommandIf(Module::has('Courses') && Module::isEnabled('Courses') && class_exists(\Modules\Courses\Spotlight\FindCourses::class), \Modules\Courses\Spotlight\FindCourses::class);
        
        Spotlight::registerCommandIf(Module::has('CourseBundles') && Module::isEnabled('CourseBundles') && class_exists(\Modules\CourseBundles\Spotlight\CourseBundles::class) , \Modules\CourseBundles\Spotlight\CourseBundles::class);
        Spotlight::registerCommandIf(Module::has('CourseBundles') && Module::isEnabled('CourseBundles') && class_exists(\Modules\CourseBundles\Spotlight\FindCourseBundles::class), \Modules\CourseBundles\Spotlight\FindCourseBundles::class);

        Spotlight::registerCommandIf(Module::has('Assignments') && Module::isEnabled('Assignments') && class_exists(\Modules\Assignments\Spotlight\Assignments::class) , \Modules\Assignments\Spotlight\Assignments::class);
        Spotlight::registerCommandIf(Module::has('Assignments') && Module::isEnabled('Assignments') && class_exists(\Modules\Assignments\Spotlight\MyAssignments::class), \Modules\Assignments\Spotlight\MyAssignments::class);

        Spotlight::registerCommandIf(Module::has('Forumwise') && Module::isEnabled('Forumwise') && class_exists(\Modules\Forumwise\Spotlight\Forumwise::class) , \Modules\Forumwise\Spotlight\Forumwise::class);

        Spotlight::registerCommandIf(Module::has('Quiz') && Module::isEnabled('Quiz') && class_exists(\Modules\Quiz\Spotlight\ManageQuizzes::class) , \Modules\Quiz\Spotlight\ManageQuizzes::class);
        Spotlight::registerCommandIf(Module::has('Quiz') && Module::isEnabled('Quiz') && class_exists(\Modules\Quiz\Spotlight\MyQuizzes::class), \Modules\Quiz\Spotlight\MyQuizzes::class);

        Spotlight::registerCommandIf(Module::has('KuponDeal') && Module::isEnabled('KuponDeal') && class_exists(\Modules\KuponDeal\Spotlight\KuponDeal::class) , \Modules\KuponDeal\Spotlight\KuponDeal::class);

        Spotlight::registerCommandIf(Module::has('Upcertify') && Module::isEnabled('Upcertify') && class_exists(\Modules\Upcertify\Spotlight\Upcertify::class) , \Modules\Upcertify\Spotlight\Upcertify::class);
        Spotlight::registerCommandIf(Module::has('Upcertify') && Module::isEnabled('Upcertify') && class_exists(\Modules\Upcertify\Spotlight\MyCertificates::class) , \Modules\Upcertify\Spotlight\MyCertificates::class);

        Spotlight::registerCommandIf(Module::has('Subscriptions') && Module::isEnabled('Subscriptions') && class_exists(\Modules\Subscriptions\Spotlight\Subscriptions::class) , \Modules\Subscriptions\Spotlight\Subscriptions::class);
    }
}
