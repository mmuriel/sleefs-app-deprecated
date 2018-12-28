@extends('layouts.sleefs-layout')
    

    @section('content')
                <section class="pos-updates">
                    <h1>Inventory Reports</h1>
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
                    <div class="pos-updates__list">
                        <table class="pos-updates__list__maintable">
                            <thead>
                                <tr>
                                    <th>
                                    Fecha
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($reports->isEmpty())
                                <tr class="update__tr 1">
                                    <td>
                                        There aren't inventory reports for this criteria
                                    </td>
                                </tr>
                                @else
                                    @foreach ($reports as $report)
                                        {!! $report->inventoryReportListView->render() !!}
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                </section>    
                    
    @endsection