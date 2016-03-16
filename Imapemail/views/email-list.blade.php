

<style type="text/css">
    th {
        display: table-cell;
        vertical-align: inherit;
        font-weight: bold;
        text-align: center;
    }
    table tr td{
        text-align: center;
    }

</style>

<div style="margin: 0 auto;width: 1000px;">
    <div style="width:100%">
        <div style="width:200px;float:right;">
            <div style="width:100px;float:left;">{{$details['start_index']}}-{{$details['end_index']}} of {{$details['count_all_msg']}}
            </div>
            <div style="width:100px;float:left;">
                <div style="width:50px;float:left;"><a href="prev">Prev</a></div>
                <div style="width:50px;float:right;"><a href="prev">Next</a></div>
            </div>
        </div>
    </div>
    <div style="float: left;min-height: 100px;width: 250px">
        {!!$details['folderView']!!}
    </div>
    <div style="min-height: 100px;">

        <table style="width: 750px">
            <tr>
                <th style="width: 200px;">
                    From
                </th>
                <th style="width: 450px;">
                    Subject
                </th>
                <th style="width: 50px">
                    Date
                </th>

            </tr>
            @foreach ($details['details'] as $eachMail)
            <tr>
                <td>
                    {{$eachMail['details']['from']}}
                </td>
                <td>
                    {{$eachMail['details']['subject']}}
                </td>
                <td>
                    {{$eachMail['details']['udate']}}
                </td>

            </tr>
            @endforeach
        </table>



    </div>
</div>