<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RepositoryController extends Controller
{
  public function listRepositories(Request $request) {
    \Log::info("In RepositoryController/listRepositories");

    $repositories = [];

    $data = [
      'code' => 200,
      'status' => 'success',
      'data' => [
        'repositories' => $repositories
      ],
    ];

    return response()->json($data);
  }
}
