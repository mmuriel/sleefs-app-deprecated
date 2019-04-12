@extends('layouts.sleefs-layout')
    

    @section('content')
                <section class="pos-updates">
                    <h1>Inventory Reports</h1>
                    <div class="report-creator-box">
                        <div class="report-creator-box__display msg-displayer" style="margin-bottom: 1rem;"></div>
                        <button class="report-creator-box__button">Generate a report</button>
                    </div>
                    <hr />
                    <div class="pos-updates__serach-box">
                        <form action="" method="get" name="search-tool">
                            {{ csrf_field() }}
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

<script>

    window.onload="myFunction()";
    let showMsgBlockOk = function(msg){
        $(".report-creator-box__display").html(msg)
        $(".report-creator-box__display").addClass('msg-displayer--ok');
    }

    let showMsgBlockError = function(msg){
        $(".report-creator-box__display").html(msg)
        $(".report-creator-box__display").addClass('msg-displayer--error');
    }

    let functionStart = function(){
        let btnGenReport = document.querySelector("button.report-creator-box__button");
        let inputCsrfField = document.querySelector('input[name="_token"]');
        btnGenReport.addEventListener("click",function(event){

            let _token = inputCsrfField.value;
            const data = JSON.stringify({
              _token: _token,
            });

            fetch('/inventoryreport', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-Token': _token,
              },
              body: data,
            }).then(response => {
              if (response.ok) {
                response.text().then(response => {
                      showMsgBlockOk('Se ha iniciado el proceso de generación de un nuevo reporte de inventario, este procedimiento tomará entre 5 y 10 minutos, despues de ese tiempo por favor recargue de nuevo esta página para ver el nuevo reporte en el listado.');
                      setTimeout(function(){
                        $(".report-creator-box__display").html("")
                        $(".report-creator-box__display").removeClass('msg-displayer--ok');
                      },6000);

                });
              }
              else{

                response.text().then(response => {
                      showMsgBlockError('Ha ocurrido un error realizando la petición de generación de un nuevo reporte, por favor contacte al administrador del sistema');
                      setTimeout(function(){
                        $(".report-creator-box__display").html("")
                        $(".report-creator-box__display").removeClass('msg-displayer--error');
                      },6000);

                });

              }
            });

        })
    }
    window.onload=functionStart;

</script>



                    
    @endsection