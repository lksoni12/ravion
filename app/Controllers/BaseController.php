<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Fetch accessible territory IDs for a user based on their role and assignments.
     * Returns null for Super Admin (global access).
     * Returns array of IDs for MR/Manager.
     */
    protected function getAccessibleTerritoryIds(int $userId, string $role): ?array
    {
        $role = strtolower(trim($role));
        if ($role === 'owner' || $role === 'admin') {
            return null; // Global access
        }

        $db = \Config\Database::connect();
        $directHqIds = array_column(
            $db->table('user_territories')
                ->select('territory_id')
                ->where('user_id', $userId)
                ->get()
                ->getResultArray(),
            'territory_id'
        );

        if (empty($directHqIds)) {
            return [0]; // Return non-existent ID to ensure query returns empty rather than all
        }

        // Fetch direct HQs and all child territories (Ex-Stations and OS under those HQs)
        $childTerritories = $db->table('territories')
            ->select('id')
            ->groupStart()
                ->whereIn('id', $directHqIds)
                ->orWhereIn('parent_id', $directHqIds)
            ->groupEnd()
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($childTerritories, 'id'));
    }
}
