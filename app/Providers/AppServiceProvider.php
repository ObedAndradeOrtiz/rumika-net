<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\CashboxSession;
use App\Models\Client;
use App\Models\ClientCharge;
use App\Models\ClientPhone;
use App\Models\ClinicalDocument;
use App\Models\ClinicalPatientAccess;
use App\Models\ClinicalPrescription;
use App\Models\ClinicalRecord;
use App\Models\ClinicalTemplate;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\InventoryAsset;
use App\Models\InventoryAssetRepair;
use App\Models\InventoryBrand;
use App\Models\InventoryCount;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\InventorySupplier;
use App\Models\InventoryUseArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\TreatmentPayment;
use App\Models\TreatmentPaymentItem;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.rumika');
        Paginator::defaultSimpleView('vendor.pagination.rumika');

        Blade::directive('money', function (string $expression) {
            return "<?php echo \\App\\Support\\Money::format({$expression}); ?>";
        });

        $this->registerAuditTrail();
    }

    private function registerAuditTrail(): void
    {
        Event::listen(Login::class, function (Login $event) {
            AuditTrail::record('login', company: $event->user->companies()->first(), description: 'Inicio sesion');
        });

        Event::listen(Logout::class, function (Logout $event) {
            AuditTrail::record('logout', company: $event->user?->companies()?->first(), description: 'Cerro sesion');
        });

        foreach ($this->auditedModels() as $model) {
            $model::created(fn ($record) => AuditTrail::logModelEvent('created', $record));
            $model::updated(fn ($record) => AuditTrail::logModelEvent('updated', $record));
            $model::deleted(fn ($record) => AuditTrail::logModelEvent('deleted', $record));
        }
    }

    private function auditedModels(): array
    {
        return [
            Appointment::class,
            AppointmentService::class,
            Branch::class,
            CashboxSession::class,
            Client::class,
            ClientCharge::class,
            ClientPhone::class,
            ClinicalDocument::class,
            ClinicalPatientAccess::class,
            ClinicalPrescription::class,
            ClinicalRecord::class,
            ClinicalTemplate::class,
            Company::class,
            Expense::class,
            ExpenseType::class,
            InventoryAsset::class,
            InventoryAssetRepair::class,
            InventoryBrand::class,
            InventoryCount::class,
            InventoryMovement::class,
            InventoryProduct::class,
            InventoryProductBatch::class,
            InventorySupplier::class,
            InventoryUseArea::class,
            Role::class,
            Service::class,
            ServicePackage::class,
            TreatmentPayment::class,
            TreatmentPaymentItem::class,
            TreatmentPlan::class,
            User::class,
        ];
    }
}
