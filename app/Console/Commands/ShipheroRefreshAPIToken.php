<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;

class ShipheroRefreshAPIToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShipheroAPI:RefreshToken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el API token de shiphero';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipheroGqlApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
        $shipheroGqlApi->refreshAccessToken();
    }
}
