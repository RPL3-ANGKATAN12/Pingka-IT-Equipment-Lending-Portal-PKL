<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loans;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoanReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_loan_report_by_month_year_department_and_borrower(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Report laptop',
            'available' => 1,
            'status' => 'tersedia',
        ]);

        $this->createLoan($item, 'Ada IT', 'IT', '2026-11-12', 'a');
        $this->createLoan($item, 'Ada HR', 'HR', '2026-11-13', 'b');
        $this->createLoan($item, 'Adaline IT', 'IT', '2026-10-12', 'c');

        $this->actingAs($admin)
            ->get(route('admin.reports', [
                'month' => 11,
                'year' => 2026,
                'department' => 'IT',
                'name' => 'Ada',
                '_review' => 1,
            ]))
            ->assertOk()
            ->assertSee('Ada IT')
            ->assertDontSee('Ada HR')
            ->assertDontSee('Adaline IT')
            ->assertSeeText('1 record')
            ->assertSee('Review PDF before download')
            ->assertSee('Name contains “Ada”')
            ->assertSee('if (true) {')
            ->assertSee(route('admin.reports.pdf', [
                'month' => 11,
                'year' => 2026,
                'department' => 'IT',
                'name' => 'Ada',
            ]));
    }

    public function test_report_review_blocks_pdf_download_when_there_are_no_matching_records(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.reports', ['name' => 'Nobody']))
            ->assertOk()
            ->assertSee('No matching records')
            ->assertSee('No records to download')
            ->assertDontSee(route('admin.reports.pdf', ['name' => 'Nobody']));
    }

    public function test_admin_can_download_a_pdf_report_using_the_selected_filters(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Report laptop',
            'available' => 1,
            'status' => 'tersedia',
        ]);
        $this->createLoan($item, 'Ada Borrower', 'IT', '2026-11-12', 'd');

        $response = $this->actingAs($admin)->get(route('admin.reports.pdf', [
            'month' => 11,
            'year' => 2026,
            'department' => 'IT',
            'name' => 'Ada',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_report_pages_are_only_available_to_admins(): void
    {
        $this->get(route('admin.reports'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.pdf'))->assertRedirect(route('login'));
    }

    private function createAdmin(): User
    {
        return User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createLoan(Item $item, string $name, string $department, string $startDate, string $tokenSuffix): Loans
    {
        return Loans::create([
            'public_token' => str_repeat($tokenSuffix, 40),
            'name' => $name,
            'email' => 'borrower@example.test',
            'departement' => $department,
            'item_id' => $item->id,
            'start_date' => $startDate,
            'status' => 'pending',
        ]);
    }
}
