<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class GithubService {

  const GITHUB_BASE_URL = 'https://api.github.com';
  const DEFAULT_PAGE_SIZE = 20;
  
  public static function getRepositories($search) {
    Log::debug("In GithubService/getRepositories");

    $filter = $search['filter'] ?? '';
    Log::debug("filter: $filter");

    $pageSize = $search['pageSize'] ?? GithubService::DEFAULT_PAGE_SIZE;
    $perPage = $pageSize > 100 ? GithubService::DEFAULT_PAGE_SIZE : $pageSize;
    Log::debug("perPage: $perPage");

    $page = $search['pageNumber'] ?? 1;
    Log::debug("page: $page");

    $query = 'php ' . $filter;
    Log::debug("query: $query");

    $queryString = http_build_query([
      'q' => $query,
      'per_page' => $perPage,
      'page' => $page,
    ]);
    Log::debug("queryString: $queryString");
    
    $path = '/search/repositories';
    $client = new Client([
      'base_uri' => GithubService::GITHUB_BASE_URL,
      'headers' => [
        'Accept' => 'application/json',
        'X-GitHub-Api-Version' => '2022-11-28'
      ],
    ]);

    $response = $client->get($path . '?' . $queryString);
    $data = json_decode($response->getBody(), true);

    $sortOrder = ($search['sortOrder'] ?? '') === 'asc' ? 'asc' : 'desc';
    Log::debug("sortOrder: $sortOrder");

    $allowedSort = ['name', 'stargazers_count', 'updated_at'];
    $sortField = $search['sortField'];
    $sort = isset($allowedSort[$sortField]) ? $sortField : 'name';
    Log::debug("sort: $sort");

    $repositories = collect($data['items'])
      ->map(function ($item) {
        return [
          'id' => $item['id'],
          'name' => $item['name'],
          'full_name' => $item['full_name'],
          'html_url' => $item['html_url'],
          'language' => $item['language'],
          'updated_at' => $item['updated_at'],
          'pushed_at' => $item['pushed_at'],
          'stargazers_count' => $item['stargazers_count'],
        ];
      });

    $sortedRepositories = GithubService::sortRepositories($repositories, $sort, $sortOrder);

    return [
      'repositories' => $sortedRepositories->values()->all(),
      'meta' => [
        'total' => $data['total_count'],
        'filter' => $filter,
        'sortOrder' => $sortOrder,
        'sortField' => $sort,
        'pageSize' => $perPage,
        'pageNumber' => $page,
      ],
    ];
  }

  private static function sortRepositories($repositories, $sortField, $sortOrder) {
    if ($sortOrder === 'asc') {
      return $repositories->sortBy($sortField);
    } else {
      return $repositories->sortByDesc($sortField);
    }
  }
}
