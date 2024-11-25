<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;
use OpenAI\Factory;

class QueryController extends Controller
{
    private $openAiClient;

    public function __construct()
    {
        $this->openAiClient = (new Factory)->withApiKey(config('services.openai.api_key'))->make();
    }

    public function index()
    {
        return view('query.index');
    }

    public function execute(Request $request)
    {
        Log::info('Query execution started');
        
        try {
            $request->validate([
                'user_query' => 'required|string|max:500',
            ]);

            $userQuery = $request->input('user_query');
            Log::info('Received user query: ' . $userQuery);

            // Construct the prompt
            $schema = "


        CREATE TABLE companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registration_code VARCHAR(8) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            legal_form VARCHAR(200),
            status VARCHAR(50),
            registration_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Contacts table
        CREATE TABLE contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            address TEXT,
            email VARCHAR(255),
            phone VARCHAR(100),
            website VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            UNIQUE KEY unique_company (company_id)
        );

        -- Representatives table
        CREATE TABLE representatives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            id_code VARCHAR(11),
            role VARCHAR(100),
            start_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            UNIQUE KEY unique_person_company (company_id, id_code)
        );

        -- Tax info table
        CREATE TABLE tax_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            vat_registered BOOLEAN DEFAULT FALSE,
            vat_registration_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            UNIQUE KEY unique_company (company_id)
        );

        -- Industries table
        CREATE TABLE industries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            industry_text VARCHAR(255) NOT NULL,
            industry_code VARCHAR(5) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            UNIQUE KEY unique_company_industry (company_id, industry_code)
        );

        -- Add indexes for better query performance
        CREATE INDEX idx_company_registration_code ON companies(registration_code);
        CREATE INDEX idx_company_registration_date ON companies(registration_date);
        CREATE INDEX idx_representatives_id_code ON representatives(id_code);
        CREATE INDEX idx_industries_code ON industries(industry_code);



            ";

            // Call OpenAI API
            try {
                $openai = \OpenAI::client(config('services.openai.api_key'));
                
                $messages = [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant that translates natural language queries into SQL queries. 
                        You must ONLY return the exact SQL query without any additional text or markup.
                         If the query mentions specific columns, you must ONLY select those columns. 
                        Do not select updated_at or created_at columns. in select specify colomns needed only, avoid using greedy select * query. 
                        Always maintain any specified ordering and limits.
                        If you select from companies table, you always include registration_code, name, registration_date.
                                            
                        '
                    ],
                    [
                        'role' => 'user',
                        'content' => "Using this database schema:\n\n{$schema}\n\nTranslate this query: {$userQuery}"
                    ]
                ];

                $inputTokens = $this->countTokens($messages);

                $response = $openai->chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,

                ]);

                $sql = trim($response->choices[0]->message->content);
                $outputTokens = $this->countTokens([['content' => $sql]]);

                // Calculate costs
                $inputCost = ($inputTokens / 1000000) * 0.15; // $0.150 per 1M tokens
                $outputCost = ($outputTokens / 1000000) * 0.60; // $0.600 per 1M tokens
                $totalCost = $inputCost + $outputCost;

                Log::info('OpenAI API response received', ['response' => $response]);

                if (!isset($response->choices[0]->message->content)) {
                    Log::error('Unexpected OpenAI API response format', ['response' => $response]);
                    throw new \Exception('Unexpected response format from OpenAI API');
                }

                Log::info('Generated SQL query', ['sql' => $sql]);

                // Security: Ensure only SELECT queries are executed
                if (stripos($sql, 'select') !== 0) {
                    Log::warning('Non-SELECT query attempted', ['sql' => $sql]);
                    return response()->json(['error' => 'Only SELECT queries are allowed.'], 400);
                }

                // Execute the query safely
                Log::info('Executing SQL query', ['sql' => $sql]);
                try {
                    $pdo = DB::connection()->getPdo();
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    Log::info('Raw query results', ['results' => $results]);

                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'results' => array_values($results), // Ensure indexed array
                            'sql' => $sql,
                            'query' => $userQuery,
                            'tokens' => [
                                'input' => $inputTokens,
                                'output' => $outputTokens,
                                'cost' => $totalCost
                            ]
                        ]);
                    }

                    // Convert results to collection for proper pagination
                    $collection = collect(array_values($results)); // Ensure indexed array
                    
                    // Paginate results
                    $perPage = 100;
                    $page = $request->get('page', 1);
                    $paginatedResults = new \Illuminate\Pagination\LengthAwarePaginator(
                        $collection->forPage($page, $perPage),
                        $collection->count(),
                        $perPage,
                        $page,
                        ['path' => $request->url(), 'query' => $request->query()]
                    );

                    return view('query.results', [
                        'results' => $paginatedResults,
                        'generatedSql' => $sql,
                        'userQuery' => $userQuery,
                        'tokens' => [
                            'input' => $inputTokens,
                            'output' => $outputTokens,
                            'cost' => $totalCost
                        ]
                    ]);

                } catch (\PDOException $e) {
                    Log::error('SQL execution error', ['error' => $e->getMessage()]);
                    return response()->json([
                        'error' => 'Error executing SQL query: ' . $e->getMessage(),
                        'query' => $userQuery,
                        'sql' => $sql,
                        'tokens' => [
                            'input' => $inputTokens,
                            'output' => $outputTokens,
                            'cost' => $totalCost
                        ]
                    ], 500);
                }

            } catch (\Exception $e) {
                Log::error('OpenAI API error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'OpenAI API error: ' . $e->getMessage(),
                        'query' => $userQuery,
                        'sql' => null,
                        'tokens' => [
                            'input' => $inputTokens,
                            'output' => 0,
                            'cost' => $inputTokens / 1000000 * 0.15
                        ]
                    ], 500);
                }
                return back()
                    ->withInput()
                    ->with('error', 'OpenAI API error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('General error in query execution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'query' => $userQuery ?? null,
                    'sql' => null
                ], 500);
            }
            return back()
                ->withInput()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    private function countTokens($messages): int
    {
        // Simple token counting approximation (4 chars = 1 token)
        $totalChars = 0;
        foreach ($messages as $message) {
            $totalChars += strlen($message['content']);
        }
        return ceil($totalChars / 4);
    }
}
