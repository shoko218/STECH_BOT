<?php

namespace App\Console;

use App\Console\Commands\IntroduceCounselingForm;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\IntroduceCounselingForm::class,
        Commands\IntroduceQuestionForm::class,
        Commands\NoticeEvent::class,
        Commands\RemindEvent::class,
        Commands\ShareEventUrl::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('counseling:introduce_form')->weeklyOn(1, '18:00');
        $schedule->command('question:introduce')->weeklyOn(1, '18:00');
        $schedule->command('event:notice')->everyFifteenMinutes();
        $schedule->command('event:remind')->dailyAt('10:00');
        $schedule->command('event:share_url')->everyFifteenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
