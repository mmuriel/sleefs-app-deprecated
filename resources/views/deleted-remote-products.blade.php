@extends('layouts.sleefs-layout')
    

    @section('content')
                <section class="product-deteled">
                    <h1>Productos Eliminados En Shopify</h1>
                    <div class="pos-updates__serach-box">
                        <form action="" method="get" name="search-tool">
                            <div class="search-criteria-box">
                                <label for="search-ini-date">Fecha inicio</label>
                                <input type="date" name="search-ini-date" id="search-ini-date" value="{{ $searchIniDate }}" placeholder="YYYY-MM-DD"/>
                            </div>
                            <div class="search-criteria-box">
                                <label for="search-end-date">Fecha fin</label>
                                <input type="date" name="search-end-date" id="search-end-date" value="{{ $searchEndDate }}" placeholder="YYYY-MM-DD" />
                            </div>
                            <div class="search-criteria-box">
                                <button type="">Buscar</button>
                            </div>
                        </form>
                    </div>
                    <p></p>
                    <h2>Listado de Productos Para Ser Borrados en Shiphero</h2>
                    <div class="pos-updates__list">
                        <form action="{{ env('APP_URL') }}/products/deleted" method="post" target="_blank" name="f-report">
                        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}" />
                        <button class="btn-delete-all">Borrar Todos</button>
                        <table class="pos-updates__list__maintable">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="#" class="btn__select-all" id="">
                                        <input type="checkbox" value="1" />
                                        </a>
                                    </th>
                                    <th>Product Title</th>
                                    <th>SKU</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {!! $htmlToPrint !!}
                            </tbody>
                        </table>
                            <button class="btn-delete-all">Borrar Todos</button>
                        </form>
                    </div>
                    
                </section> 
                    
    @endsection