<?php

use App\Actions\Admin\AddConsultationNote;
use App\Actions\Admin\AddMilestone;
use App\Actions\Admin\AssessAllProjects;
use App\Actions\Admin\AssessProjectHealth;
use App\Actions\Admin\AssignExpert;
use App\Actions\Admin\BuildQuotation;
use App\Actions\Admin\CreateLeadForClient;
use App\Actions\Admin\CreatePaymentLink;
use App\Actions\Admin\CreatePayout;
use App\Actions\Admin\CreateQuotationLink;
use App\Actions\Admin\DeleteMilestone;
use App\Actions\Admin\DeletePlan;
use App\Actions\Admin\DeleteQuotationTemplate;
use App\Actions\Admin\DeleteProjectDocument;
use App\Actions\Admin\DownloadInvoicePdf;
use App\Actions\Admin\DownloadPayoutProof;
use App\Actions\Admin\DownloadProjectDocument;
use App\Actions\Admin\DraftFollowUp;
use App\Actions\Admin\DraftProjectUpdate;
use App\Actions\Admin\ExportClients;
use App\Actions\Admin\ExportConsultations;
use App\Actions\Admin\ExportPayments;
use App\Actions\Admin\ExportPayouts;
use App\Actions\Admin\GenerateAiDraft;
use App\Actions\Admin\ImportClients;
use App\Actions\Admin\ListActivity;
use App\Actions\Admin\ListClients;
use App\Actions\Admin\ListConsultations;
use App\Actions\Admin\ListExperts;
use App\Actions\Admin\ListFollowUps;
use App\Actions\Admin\ListInvoices;
use App\Actions\Admin\ListLeads;
use App\Actions\Admin\ListNotifications;
use App\Actions\Admin\ListPaymentLinks;
use App\Actions\Admin\ListPayments;
use App\Actions\Admin\ListPayouts;
use App\Actions\Admin\ListPlans;
use App\Actions\Admin\ListProjects;
use App\Actions\Admin\ListQuotationTemplates;
use App\Actions\Admin\OnboardExpert;
use App\Actions\Admin\RefreshPayoutStatus;
use App\Actions\Admin\ReorderMilestones;
use App\Actions\Admin\ReorderPlans;
use App\Actions\Admin\ScheduleLeadFollowUp;
use App\Actions\Admin\ScoreAllLeads;
use App\Actions\Admin\ScoreLead;
use App\Actions\Admin\SearchContacts;
use App\Actions\Admin\SendPaymentLink;
use App\Actions\Admin\SetConsultationPrice;
use App\Actions\Admin\ShowAdminDashboard;
use App\Actions\Admin\ShowClient;
use App\Actions\Admin\ShowConsultation as ShowAdminConsultation;
use App\Actions\Admin\ShowExpert;
use App\Actions\Admin\ShowExpertForm;
use App\Actions\Admin\ShowInvoice;
use App\Actions\Admin\ShowPaymentLink;
use App\Actions\Admin\ShowPayoutForm;
use App\Actions\Admin\ShowPlanForm;
use App\Actions\Admin\ShowProject;
use App\Actions\Admin\ShowQuotationTemplateForm;
use App\Actions\Admin\ShowSettings;
use App\Actions\Admin\StoreClient;
use App\Actions\Admin\StoreExpert;
use App\Actions\Admin\StoreLead;
use App\Actions\Admin\StorePlan;
use App\Actions\Admin\StoreQuotationTemplate;
use App\Actions\Admin\StripeRefresh;
use App\Actions\Admin\StripeReturn;
use App\Actions\Admin\SuggestMilestones;
use App\Actions\Admin\SuggestNextSteps;
use App\Actions\Admin\SummarizeNotes;
use App\Actions\Admin\UpdateClient;
use App\Actions\Admin\UpdateConsultationStatus;
use App\Actions\Admin\UpdateExpert;
use App\Actions\Admin\UpdateLeadStage;
use App\Actions\Admin\UpdateMilestone;
use App\Actions\Admin\UpdateMilestoneStatus;
use App\Actions\Admin\UpdatePlan;
use App\Actions\Admin\UpdateQuotationTemplate;
use App\Actions\Admin\UpdateSettings;
use App\Actions\Admin\UploadProjectDocuments;
use App\Actions\Clients\AskAssistant;
use App\Actions\Clients\MagicLogin;
use App\Actions\Clients\ShowClientConsultation;
use App\Actions\Clients\ShowClientConsultations;
use App\Actions\Clients\ShowClientDashboard;
use App\Actions\Clients\ShowClientInvoice;
use App\Actions\Clients\ShowClientInvoices;
use App\Actions\Clients\ShowClientNotifications;
use App\Actions\Clients\ShowClientPayments;
use App\Actions\Clients\ShowClientProfile;
use App\Actions\Clients\ShowClientProject;
use App\Actions\Clients\ShowClientProjects;
use App\Actions\Clients\UpdateClientProfile;
use App\Actions\Consultations\AcceptQuotation;
use App\Actions\Consultations\AskConcierge;
use App\Actions\Consultations\CaptureGatewayOrder;
use App\Actions\Consultations\CompleteConsultation;
use App\Actions\Consultations\CreateGatewaySession;
use App\Actions\Consultations\DownloadQuotationPdf;
use App\Actions\Consultations\RequestCustomPlan;
use App\Actions\Consultations\ReturnFromGateway;
use App\Actions\Consultations\ShowCheckout;
use App\Actions\Consultations\ShowQuotation;
use App\Actions\Consultations\StartPlanCheckout;
use App\Actions\Consultations\StoreConsultationRequest;
use App\Actions\Notifications\MarkNotificationsRead;
use App\Actions\Paypal\PaypalWebhook;
use App\Actions\Plans\GetActivePlans;
use App\Actions\Stripe\StripeWebhook;
use App\Actions\Tickets\DeleteConsultationAttachment;
use App\Actions\Tickets\DownloadConsultationAttachment;
use App\Actions\Tickets\UploadConsultationAttachments;
use App\Data\Plan\PlanData;
use App\Http\Middleware\RedirectAdminsFromPortal;
use App\Models\Country;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::get('/', fn () => Inertia::render('welcome', [
    'plans' => GetActivePlans::run(),
]))->name('home');

// Public, unauthenticated write endpoints are throttled by IP to stop abuse
// (junk rows, hammering the payment providers).
Route::post('/plans/{plan:slug}/buy', StartPlanCheckout::class)->middleware('throttle:20,1')->name('consultation.buy');

Route::get('/consultation/{consultationRequest}/checkout', ShowCheckout::class)->name('consultation.checkout');
Route::get('/consultation/{consultationRequest}/complete', CompleteConsultation::class)->name('consultation.complete');

// Public quotation document — the client reviews the offer, confirms their
// details and accepts (which drops them onto the checkout above).
Route::get('/quote/{consultationRequest}', ShowQuotation::class)->name('consultation.quote');
Route::post('/quote/{consultationRequest}/accept', AcceptQuotation::class)->middleware('throttle:10,1')->name('consultation.quote.accept');
Route::get('/quote/{consultationRequest}/pdf', DownloadQuotationPdf::class)->name('consultation.quote.pdf');

// Gateway-agnostic checkout. Embedded rails (Stripe) redirect here on success;
// "buttons" rails (PayPal SDK) call order/capture from the on-page buttons.
Route::get('/consultation/{consultationRequest}/pay/{gateway}/return', ReturnFromGateway::class)->name('consultation.pay.return');
Route::post('/consultation/{consultationRequest}/pay/{gateway}/session', CreateGatewaySession::class)->middleware('throttle:payment-session')->name('consultation.pay.session');
Route::post('/consultation/{consultationRequest}/pay/{gateway}/capture', CaptureGatewayOrder::class)->middleware('throttle:30,1')->name('consultation.pay.capture');

Route::post('/paypal/webhook', PaypalWebhook::class)->name('paypal.webhook');
Route::post('/stripe/webhook', StripeWebhook::class)->name('stripe.webhook');

Route::get('/portal/access/{user}', MagicLogin::class)->middleware('signed')->name('portal.magic-login');

Route::get('/plans/{plan:slug}/consult', fn (Plan $plan) => Inertia::render('consultation/request', [
    'plan' => PlanData::fromModel($plan),
    'countries' => Country::options(),
]))->name('consultation.create');
Route::post('/plans/{plan:slug}/consult', StoreConsultationRequest::class)->middleware('throttle:10,1')->name('consultation.store');

// Request a tailored (custom) plan — no plan selected; admin prices it later.
Route::get('/custom-plan', fn () => Inertia::render('consultation/custom-plan', [
    'countries' => Country::options(),
]))->name('custom-plan');
// Tighter limit: each submission dispatches a billed AI lead-scoring job.
Route::post('/custom-plan', RequestCustomPlan::class)->middleware('throttle:5,1')->name('custom-plan.store');

// Public pre-sales concierge — unauthenticated, so throttled by IP.
Route::post('/concierge/ask', AskConcierge::class)->middleware('throttle:8,1')->name('concierge.ask');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', ShowAdminDashboard::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', RoleMiddleware::using('admin')])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('summarize-notes', SummarizeNotes::class)->middleware('throttle:30,1')->name('summarize-notes');
        Route::get('consultations', ListConsultations::class)->name('consultations');
        Route::get('consultations/export', ExportConsultations::class)->name('consultations.export');
        Route::get('consultations/{consultationRequest}', ShowAdminConsultation::class)->name('consultation');
        Route::get('consultations/{consultationRequest}/payment-link', ShowPaymentLink::class)->name('consultation.payment-link');
        Route::post('consultations/{consultationRequest}/price', SetConsultationPrice::class)->name('consultations.price');
        Route::post('consultations/{consultationRequest}/quotation', BuildQuotation::class)->name('consultations.quotation');
        Route::post('consultations/{consultationRequest}/send-link', SendPaymentLink::class)->name('consultations.send-link');
        Route::patch('consultations/{consultationRequest}/status', UpdateConsultationStatus::class)->name('consultations.status');
        Route::post('consultations/{consultationRequest}/notes', AddConsultationNote::class)->name('consultations.notes');
        Route::post('consultations/{consultationRequest}/ai-draft', GenerateAiDraft::class)->name('consultations.ai-draft');
        Route::post('consultations/{consultationRequest}/next-steps', SuggestNextSteps::class)->name('consultations.next-steps');
        Route::post('consultations/{consultationRequest}/assign', AssignExpert::class)->name('consultations.assign');
        Route::post('consultations/{consultationRequest}/attachments', UploadConsultationAttachments::class)->name('consultations.attachments.store');
        Route::get('consultations/{consultationRequest}/attachments/{media}', DownloadConsultationAttachment::class)->name('consultations.attachments.show');
        Route::delete('consultations/{consultationRequest}/attachments/{media}', DeleteConsultationAttachment::class)->name('consultations.attachments.destroy');
        Route::get('payment-links', ListPaymentLinks::class)->name('payment-links');
        Route::post('payment-links', CreatePaymentLink::class)->name('payment-links.store');
        Route::post('payment-links/quotation', CreateQuotationLink::class)->name('payment-links.quotation');

        // Reusable quotation templates.
        Route::get('quotation-templates', ListQuotationTemplates::class)->name('quotation-templates');
        Route::get('quotation-templates/create', ShowQuotationTemplateForm::class)->name('quotation-templates.create');
        Route::post('quotation-templates', StoreQuotationTemplate::class)->name('quotation-templates.store');
        Route::get('quotation-templates/{quotationTemplate}/edit', ShowQuotationTemplateForm::class)->name('quotation-templates.edit');
        Route::patch('quotation-templates/{quotationTemplate}', UpdateQuotationTemplate::class)->name('quotation-templates.update');
        Route::delete('quotation-templates/{quotationTemplate}', DeleteQuotationTemplate::class)->name('quotation-templates.destroy');
        Route::get('leads', ListLeads::class)->name('leads');
        Route::get('follow-ups', ListFollowUps::class)->name('follow-ups');
        Route::get('contacts/search', SearchContacts::class)->name('contacts.search');
        Route::post('leads', StoreLead::class)->name('leads.store');
        Route::post('leads/{consultationRequest}/stage', UpdateLeadStage::class)->name('leads.stage');
        Route::post('leads/{consultationRequest}/follow-up', ScheduleLeadFollowUp::class)->name('leads.follow-up');
        Route::post('leads/{consultationRequest}/follow-up-draft', DraftFollowUp::class)->name('leads.follow-up-draft');
        Route::post('leads/score-all', ScoreAllLeads::class)->name('leads.score-all');
        Route::post('leads/{consultationRequest}/score', ScoreLead::class)->name('leads.score');
        Route::get('payments', ListPayments::class)->name('payments');
        Route::get('payments/export', ExportPayments::class)->name('payments.export');
        Route::get('activity', ListActivity::class)->name('activity');
        Route::get('settings', ShowSettings::class)->name('settings');
        Route::patch('settings', UpdateSettings::class)->name('settings.update');
        Route::get('clients', ListClients::class)->name('clients');
        Route::get('clients/export', ExportClients::class)->name('clients.export');
        Route::post('clients/import', ImportClients::class)->name('clients.import');
        Route::post('clients', StoreClient::class)->name('clients.store');
        Route::post('clients/{client}/lead', CreateLeadForClient::class)->name('clients.lead');
        Route::patch('clients/{client}', UpdateClient::class)->name('clients.update');
        Route::get('clients/{client}', ShowClient::class)->name('clients.show');
        Route::get('experts', ListExperts::class)->name('experts');
        Route::get('experts/create', ShowExpertForm::class)->name('experts.create');
        Route::post('experts', StoreExpert::class)->name('experts.store');
        Route::get('experts/{expert}', ShowExpert::class)->name('experts.show');
        Route::get('experts/{expert}/edit', ShowExpertForm::class)->name('experts.edit');
        Route::patch('experts/{expert}', UpdateExpert::class)->name('experts.update');
        Route::post('experts/{expert}/payout/onboard', OnboardExpert::class)->name('experts.payout.onboard');
        Route::get('experts/{expert}/payout/refresh', RefreshPayoutStatus::class)->name('experts.payout.refresh');
        Route::get('experts/{expert}/stripe/return', StripeReturn::class)->name('experts.stripe.return');
        Route::get('experts/{expert}/stripe/refresh', StripeRefresh::class)->name('experts.stripe.refresh');

        Route::get('payouts', ListPayouts::class)->name('payouts');
        Route::get('payouts/export', ExportPayouts::class)->name('payouts.export');
        Route::get('payouts/create', ShowPayoutForm::class)->name('payouts.create');
        Route::post('payouts', CreatePayout::class)->name('payouts.store');
        Route::get('payouts/{payout}/proof/{media}', DownloadPayoutProof::class)->name('payouts.proof.show');

        Route::get('projects', ListProjects::class)->name('projects');
        Route::post('projects/assess-all', AssessAllProjects::class)->name('projects.assess-all');
        Route::get('projects/{project}', ShowProject::class)->name('projects.show');
        Route::post('projects/{project}/health', AssessProjectHealth::class)->name('projects.health');
        Route::post('projects/{project}/update-draft', DraftProjectUpdate::class)->name('projects.update-draft');
        Route::post('projects/{project}/milestones', AddMilestone::class)->name('projects.milestones.store');
        Route::post('projects/{project}/milestones/suggest', SuggestMilestones::class)->name('projects.milestones.suggest');
        Route::post('projects/{project}/milestones/reorder', ReorderMilestones::class)->name('projects.milestones.reorder');
        Route::patch('projects/{project}/milestones/{milestone}/status', UpdateMilestoneStatus::class)->name('projects.milestones.status');
        Route::patch('projects/{project}/milestones/{milestone}', UpdateMilestone::class)->name('projects.milestones.update');
        Route::delete('projects/{project}/milestones/{milestone}', DeleteMilestone::class)->name('projects.milestones.destroy');
        Route::post('projects/{project}/documents', UploadProjectDocuments::class)->name('projects.documents.store');
        Route::get('projects/{project}/documents/{media}', DownloadProjectDocument::class)->name('projects.documents.show');
        Route::delete('projects/{project}/documents/{media}', DeleteProjectDocument::class)->name('projects.documents.destroy');

        Route::get('invoices', ListInvoices::class)->name('invoices');
        Route::get('invoices/{invoice}', ShowInvoice::class)->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', DownloadInvoicePdf::class)->name('invoices.pdf');

        Route::get('plans', ListPlans::class)->name('plans');
        Route::get('plans/create', ShowPlanForm::class)->name('plans.create');
        Route::post('plans', StorePlan::class)->name('plans.store');
        Route::post('plans/reorder', ReorderPlans::class)->name('plans.reorder');
        Route::get('plans/{plan}/edit', ShowPlanForm::class)->name('plans.edit');
        Route::patch('plans/{plan}', UpdatePlan::class)->name('plans.update');
        Route::delete('plans/{plan}', DeletePlan::class)->name('plans.destroy');

        Route::get('notifications', ListNotifications::class)->name('notifications');
    });

Route::middleware(['auth', RedirectAdminsFromPortal::class])->prefix('portal')->name('portal.')->group(function () {
    Route::redirect('/', '/portal/dashboard');
    Route::get('dashboard', ShowClientDashboard::class)->name('dashboard');
    Route::post('assistant/ask', AskAssistant::class)->middleware('throttle:15,1')->name('assistant.ask');
    Route::get('consultations', ShowClientConsultations::class)->name('consultations');
    Route::get('consultations/{consultationRequest}', ShowClientConsultation::class)->name('consultation');
    Route::get('consultations/{consultationRequest}/attachments/{media}', DownloadConsultationAttachment::class)->name('consultation.attachments.show');
    Route::get('projects', ShowClientProjects::class)->name('projects');
    Route::get('projects/{project}', ShowClientProject::class)->name('project');
    Route::get('projects/{project}/documents/{media}', DownloadProjectDocument::class)->name('project.documents.show');
    Route::get('invoices', ShowClientInvoices::class)->name('invoices');
    Route::get('invoices/{invoice}', ShowClientInvoice::class)->name('invoice');
    Route::get('invoices/{invoice}/pdf', DownloadInvoicePdf::class)->name('invoice.pdf');
    Route::get('payments', ShowClientPayments::class)->name('payments');
    Route::get('notifications', ShowClientNotifications::class)->name('notifications');
    Route::get('profile', ShowClientProfile::class)->name('profile');
    Route::patch('profile', UpdateClientProfile::class)->name('profile.update');
});

Route::middleware('auth')->group(function () {
    Route::post('notifications/read-all', MarkNotificationsRead::class)->name('notifications.read-all');
    Route::post('notifications/{id}/read', MarkNotificationsRead::class)->name('notifications.read');
});

require __DIR__.'/settings.php';
