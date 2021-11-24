@extends('layouts.sleefs-layout')
    

    @section('content')
                <section class="product-deteled">
                    <h1>Productos Eliminados En Shopify</h1>
                    <div class="pos-updates__list">
                        <form action="{{ env('APP_URL') }}/products/deleted" method="post" target="_blank" name="f-report">
                        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}" />
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