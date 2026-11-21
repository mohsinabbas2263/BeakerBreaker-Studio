<?php

namespace Tests\Feature;

use App\Filament\Pages\ImportStudents;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CSRF and file upload checks for the administrator panel (SR-04).
 * The API is not here: it authenticates by bearer token, which a browser never
 * attaches by itself, so a forged cross-site request carries no credentials.
 */
class WebSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** The panel signs in by session cookie, so a cross-site post without the token is refused. */
    public function test_a_panel_post_without_a_csrf_token_is_refused(): void
    {
        $admin = User::factory()->administrator()->create();

        // Laravel skips the CSRF check while running tests; outside "testing" it applies.
        $this->app['env'] = 'production';

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertStatus(419);

        $this->assertAuthenticatedAs($admin);
    }

    /** Only an uploaded file is read: a path put into the form's state is not opened. */
    public function test_the_import_does_not_read_a_path_from_the_server_disk(): void
    {
        Storage::put('secret.csv', "name,email,class\nLeaked Line,leak,Class 9-A\n");

        $school = School::create([
            'name' => 'Pilot', 'district' => 'Gujrat', 'board' => 'punjab', 'licensed_grades' => [9],
            'licence_start' => now(), 'licence_end' => now()->addYear(), 'student_cap' => 0,
        ]);
        $this->actingAs(User::factory()->administrator()->create(['school_id' => $school->id]));

        try {
            Livewire::test(ImportStudents::class)
                ->set('data.file', ['forged' => 'secret.csv'])
                ->call('import')
                ->assertDontSee('Leaked Line')
                ->assertDontSee('leak');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertSame(0, User::where('role', 'student')->count());
    }
}
