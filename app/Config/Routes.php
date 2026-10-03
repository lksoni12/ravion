<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static function () {
	$indexPath = FCPATH . 'index.html';
	if (! is_file($indexPath)) {
		return service('response')->setStatusCode(404)->setBody('index.html not found');
	}

	return service('response')
		->setHeader('Content-Type', 'text/html; charset=UTF-8')
		->setBody((string) file_get_contents($indexPath));
});

$routes->get('/phadmin', 'AuthController::login');
$routes->get('/phadmin/login', 'AuthController::login');
$routes->post('/phadmin/login', 'AuthController::authenticate');
$routes->get('/phadmin/logout', 'AuthController::logout');
$routes->get('/api/db/sync', 'DbSyncController::sync');
$routes->post('/api/db/sync', 'DbSyncController::sync');
$routes->get('/api/db/clear', 'DbSyncController::clear');
$routes->post('/api/db/clear', 'DbSyncController::clear');
$routes->get('/api/db/backup', 'DbSyncController::backup');
$routes->post('/api/db/backup', 'DbSyncController::backup');
$routes->get('/api/db/backups', 'DbSyncController::listBackups');
$routes->match(['get', 'post'], '/api/db/restore', 'DbSyncController::restore');
$routes->get('/api/e-detailing', 'EDetailingApiController::index');
$routes->post('/api/e-detailing/start-call', 'EDetailingApiController::startCall');
$routes->post('/api/e-detailing/end-call/(:num)', 'EDetailingApiController::endCall/$1');


$routes->group('phadmin', ['filter' => 'authguard'], static function ($routes) {
	$routes->get('/', 'AdminController::dashboard');
	$routes->get('dashboard', 'AdminController::dashboard');
	$routes->get('dashboard/export', 'AdminController::exportDashboard');

	$routes->get('users', 'AdminController::users');
	$routes->post('users', 'AdminController::createUser');
	$routes->post('users/update/(:num)', 'AdminController::updateUser/$1');
	$routes->post('users/toggle/(:num)', 'AdminController::toggleUser/$1');
	$routes->post('users/delete/(:num)', 'AdminController::deleteUser/$1');
	$routes->post('settings/da', 'AdminController::updateDaSettings');

	$routes->get('territories', 'AdminController::territories');
	$routes->post('territories', 'AdminController::createTerritory');
	$routes->post('territories/update/(:num)', 'AdminController::updateTerritory/$1');
	$routes->post('territories/delete/(:num)', 'AdminController::deleteTerritory/$1');

	$routes->get('products', 'AdminController::products');
	$routes->post('products', 'AdminController::createProduct');
	$routes->post('products/reorder', 'AdminController::reorderProducts');
	$routes->post('products/update/(:num)', 'AdminController::updateProduct/$1');
	$routes->post('products/delete/(:num)', 'AdminController::deleteProduct/$1');

	$routes->get('doctors', 'AdminController::doctors');
	$routes->post('doctors', 'AdminController::createDoctor');
	$routes->post('doctors/update/(:num)', 'AdminController::updateDoctor/$1');
	$routes->post('doctors/approve/(:num)', 'AdminController::approveDoctor/$1');
	$routes->post('doctors/bulk-approve', 'AdminController::bulkApproveDoctors');
	$routes->post('doctors/delete/(:num)', 'AdminController::deleteDoctor/$1');

	$routes->get('birthdays', 'AdminController::birthdays');
	$routes->post('birthdays', 'AdminController::createReminder');
	$routes->post('birthdays/update/(:num)', 'AdminController::updateReminder/$1');
	$routes->post('birthdays/delete/(:num)', 'AdminController::deleteReminder/$1');
	$routes->match(['get', 'post'], 'send-birthday-reminders', 'AdminController::sendBirthdayReminders');

	$routes->get('visits', 'AdminController::visits');
	$routes->post('visits', 'AdminController::createVisit');

	$routes->get('chemists', 'AdminController::chemists');
	$routes->post('chemists', 'AdminController::createChemist');
	$routes->post('chemists/update/(:num)', 'AdminController::updateChemist/$1');
	$routes->post('chemists/approve/(:num)', 'AdminController::approveChemist/$1');
	$routes->post('chemists/delete/(:num)', 'AdminController::deleteChemist/$1');

	$routes->get('expenses', 'AdminController::expenses');
	$routes->post('expenses', 'AdminController::createExpense');
	$routes->post('expenses/draft', 'AdminController::saveDraft');
	$routes->post('expenses/status/(:num)', 'AdminController::updateExpenseStatus/$1');

	$routes->get('cfa-stockists', 'OperationsController::cfaStockists');
	$routes->post('cfas', 'OperationsController::createCfa');
	$routes->post('cfas/update/(:num)', 'OperationsController::updateCfa/$1');
	$routes->post('cfas/delete/(:num)', 'OperationsController::deleteCfa/$1');
	$routes->post('stockists', 'OperationsController::createStockist');
	$routes->post('stockists/update/(:num)', 'OperationsController::updateStockist/$1');
	$routes->post('stockists/delete/(:num)', 'OperationsController::deleteStockist/$1');

	$routes->get('sponsorships', 'OperationsController::sponsorships');
	$routes->post('sponsorships', 'OperationsController::createSponsorship');
	$routes->post('sponsorships/update/(:num)', 'OperationsController::updateSponsorship/$1');
	$routes->post('sponsorships/status/(:num)', 'OperationsController::updateSponsorshipStatus/$1');
	$routes->post('sponsorships/delete/(:num)', 'OperationsController::deleteSponsorship/$1');

	$routes->get('sales-entries', 'OperationsController::salesEntries');
	$routes->get('sales-entries/detail', 'OperationsController::salesEntryDetail');
	$routes->get('sales-entries/export', 'OperationsController::exportDetailedMatrix');
	$routes->post('sales-entries/save-matrix', 'OperationsController::saveDetailedMatrix');
	$routes->post('sales-entries', 'OperationsController::createSalesEntry');
	$routes->post('sales-entries/update/(:num)', 'OperationsController::updateSalesEntry/$1');
	$routes->post('sales-entries/status/(:num)', 'OperationsController::updateSalesStatus/$1');

	$routes->get('slides', 'OperationsController::slides');
	$routes->post('slides', 'OperationsController::createSlide');
	$routes->post('slides/reorder', 'AdminController::reorderSlides');
	$routes->post('slides/toggle/(:num)', 'OperationsController::toggleSlide/$1');
	$routes->post('slides/delete/(:num)', 'OperationsController::deleteSlide/$1');

	$routes->get('plans', 'AdminController::plans');
	$routes->post('plans/submit', 'AdminController::createMonthPlan');
	$routes->post('plans/draft', 'AdminController::savePlanDraft');
	$routes->post('plans/approve-month', 'AdminController::approveMonthPlan');
	$routes->post('plans/reject-month', 'AdminController::rejectMonthPlan');
	$routes->post('plans', 'AdminController::createPlan');
	$routes->post('plans/submit-month', 'AdminController::submitPlanMonth');
	$routes->post('plans/lock-month', 'AdminController::lockPlanMonth');
	$routes->post('plans/status/(:num)', 'AdminController::updatePlanStatus/$1');

	$routes->get('e-detailing', 'EDetailingController::index');
	$routes->post('e-detailing/add-doctor', 'EDetailingController::addDoctor');
	$routes->post('e-detailing/add-chemist', 'EDetailingController::addChemist');
	$routes->post('e-detailing/delete-doctors', 'EDetailingController::deleteDoctors');
	$routes->post('e-detailing/start-call', 'EDetailingController::startCall');
	$routes->get('e-detailing/presentation/(:num)', 'EDetailingController::presentation/$1');
	$routes->post('e-detailing/end-call/(:num)', 'EDetailingController::endCall/$1');

	$routes->get('reports', 'AdminController::reports');
	$routes->get('reports/planned-doctors', 'AdminController::getPlannedDoctors');
	$routes->post('reports/submit', 'AdminController::submitReport');
	$routes->post('reports/toggle-lock', 'AdminController::toggleReportLock');
	$routes->get('reports/export', 'AdminController::exportReports');

	$routes->post('settings/logo', 'AdminController::uploadLogo');
});
