<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Services\GithubService;

class RepositoryController extends Controller
{
  public function listRepositories(Request $request) {
    Log::debug("In RepositoryController/listRepositories");

    $queryParams = $request->query();

    $filter = $request->query('filter');
    Log::debug("filter: $filter");

    $sortOrder = $request->query('sortOrder');
    Log::debug("sortOrder: $sortOrder");

    $sortField = $request->query('sortField');
    Log::debug("sortField: $sortField");

    $pageSize = $request->query('pageSize');
    Log::debug("pageSize: $pageSize");

    $pageNumber = $request->query('pageNumber');
    Log::debug("pageNumber: $pageNumber");

    $search = [
      'filter' => $filter,
      'sortOrder' => $sortOrder,
      'sortField' => $sortField,
      'pageSize' => $pageSize,
      'pageNumber' => $pageNumber,
    ];

    $searchResult = GithubService::getRepositories($search);

    $data = [
      'code' => 200,
      'status' => 'success',
      'data' => $searchResult,
    ];

    return response()->json($data);
  }
}
