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

    $sortOrder = ($search['sortOrder'] ?? '') === 'asc' ? 'asc' : 'desc';
    Log::debug("sortOrder: $sortOrder");

    $sortField = $search['sortField'];
    Log::debug("sortField: $sortField");

    $sortedRepositories = GithubService::sortRepositories($repositories, $sortField, $sortOrder);

    return [
      'repositories' => $sortedRepositories->values()->all(),
      'meta' => [
        'total' => $data['total_count'],
        'filter' => $filter,
        'sortOrder' => $sortOrder,
        'sortField' => $sortField,
        'pageSize' => $perPage,
        'pageNumber' => $page,
      ],
    ];
  }

  private static function sortRepositories($repositories, $sortField, $sortOrder) {
    return $repositories->sort(function ($a, $b) use($sortField, $sortOrder) {
      if ($sortField == 'stargazers_count') {
        if ($sortOrder == 'asc') {
          return $a['stargazers_count'] - $b['stargazers_count'];
        } else {
          return $b['stargazers_count'] - $a['stargazers_count'];
        }
      } else if ($sortField == 'updated_at') {
        $date1 = strtotime($a['updated_at']);
        $date2 = strtotime($b['updated_at']);

        if ($sortOrder == 'asc') {
          return $date1 - $date2;
        } else {
          return $date2 - $date1;
        }
      } else {
        if ($sortOrder == 'asc') {
          return strnatcasecmp($a['name'], $b['name']);
        } else {
          return strnatcasecmp($b['name'], $a['name']);
        }
      }
    });
  }
}
