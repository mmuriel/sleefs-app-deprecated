@extends('layouts.sleefs-layout')
    

    @section('content')
                <section class="pos-updates">
                    <h1>Listado de actualizaciones de órdenes de pedido (POs)</h1>
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
                                <label for="search-po">PO ID</label>
                                <input type="text" name="search-po" id="search-po" value="{{ $searchPo }}"/>
                            </div>
                            <div class="search-criteria-box">
                                <button type="">Buscar</button>
                            </div>
                        </form>
                    </div>
                    <div class="pos-updates__list">
                        <form action="{{ env('APP_URL') }}/report" method="post" target="_blank" name="f-report">
                        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}" />
                        <table class="pos-updates__list__maintable">
                            <thead>
                                <tr>
                                    <th>
                                    </th>
                                    <th>
                                     PO ID   
                                    </th>
                                    <th>
                                    Fecha - Hora
                                    </th>
                                    <th>
                                     Total Items
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($poupdates->isEmpty())
                                <tr class="update__tr 1">
                                    <td colspan="5">
                                        There aren't PO's updates for this criteria
                                    </td>
                                </tr>
                                @else
                                    @foreach ($poupdates as $poupdate)
                                        {!! $poupdate->updateView->render() !!}
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        @if ($poupdates->isEmpty())
                    
                        @else
                            <button class="btn-report">Report to print</button>
                        @endif
                        </form>
                    </div>
                    
                </section>    
                    
    @endsection