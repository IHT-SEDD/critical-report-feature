<!DOCTYPE html>
<html>

<body>
 <div>
  <table>
   <tr>
    <td colspan="6" style="font-weight:bold">LAPORAN NILAI KRITIS LABORATORIUM</td>
   </tr>
   <tr>
    <td colspan="6" style="font-weight:bold">Nama Institusi : {{ config('critical-report.institution_name') }}</td>
   </tr>
   <tr>
    <td colspan="6" style="font-weight:bold">Periode Tanggal : {{ $startDate }} - {{ $endDate }}</td>
   </tr>
  </table>

  <br>

  <table style="border: 1px solid black; margin: 5px; border-collapse: collapse;">
   <thead>
    <tr>
     <th style="text-align: center;">No</th>
     <th style="text-align: center;">Tanggal</th>
     <th style="text-align: center;">No Lab</th>
     <th style="text-align: center;">No MR</th>
     <th style="text-align: center;">Nama Pasien</th>
     <th style="text-align: center;">Asal Ruangan</th>
     <th style="text-align: center;">Pemeriksaan</th>
     <th style="text-align: center;">Hasil</th>
     <th style="text-align: center;">Nilai Normal</th>
     <th style="text-align: center;">Jam Val</th>
     <th style="text-align: center;">Jam Lapor</th>
     <th style="text-align: center;">TAT Lapor</th>
    </tr>
   </thead>
   <tbody>
    @php
    $index = 1;
    $total = 0;
    $count_tat_dibawah_target = 0;
    $count_tat_diatas_target = 0;
    $target_tat_in_seconds = (int) config('critical-report.target_tat_minutes', 30) * 60;
    @endphp

    @foreach($reportData as $data)

    @php
    $validate_time = \Carbon\Carbon::parse($data->validate_time);
    $report_time = \Carbon\Carbon::parse($data->report_time);

    $tat_in_seconds = $report_time->diffInSeconds($validate_time, false);

    $tat_is_valid = $tat_in_seconds >= 0;

    if ($tat_is_valid) {
        $tat_jam = floor($tat_in_seconds / 3600);
        $tat_menit = floor(($tat_in_seconds % 3600) / 60);
        $tat_detik = $tat_in_seconds % 60;
        $tat_time = sprintf('%02d:%02d:%02d', $tat_jam, $tat_menit, $tat_detik);
    } else {
        $tat_time = '-';
    }
    @endphp

    <?php
    if ($tat_is_valid) {
        if ($tat_in_seconds <= $target_tat_in_seconds) {
            $count_tat_dibawah_target++;
        } else {
            $count_tat_diatas_target++;
        }
    }
    ?>
    <tr>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $index }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y', strtotime($data->input_time)) }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->no_lab }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_medrec }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_name }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->room_name }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->test_name }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->global_result }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{!! $data->normal_value !!}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y H:i:s', strtotime($data->validate_time)) }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y H:i:s', strtotime($data->report_time)) }}</td>
     <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $tat_time }} </td>
    </tr>
    @php
    $index++;
    if ($tat_is_valid) {
    $total += $tat_in_seconds;
    }
    @endphp
    @endforeach
   </tbody>
  </table>
 </div>

 @php
 $jumlah_data_valid = $count_tat_dibawah_target + $count_tat_diatas_target;

 if ($jumlah_data_valid > 0) {
 $average = $total / $jumlah_data_valid;

 $jam = floor($average / 3600);
 $menit = floor(($average % 3600) / 60);
 $detik = $average % 60;

 $percentage_dibawah_target = round(($count_tat_dibawah_target / $jumlah_data_valid) * 100);
 $percentage_diatas_target = round(($count_tat_diatas_target / $jumlah_data_valid) * 100);
 } else {
 $jam = $menit = $detik = 0;
 $percentage_dibawah_target = 0;
 $percentage_diatas_target = 0;
 }
 @endphp

 <table>
  <tr>
   <td colspan="4" style="font-weight:bold">
    Rata-rata TAT Lapor : {{ $jam }} Jam {{ $menit }} Menit {{ $detik }} Detik
   </td>
  </tr>
  <tr>
   <td colspan="4" style="font-weight:bold">
    Target &lt;= {{ config('critical-report.target_tat_minutes', 30) }} Menit : {{ $count_tat_dibawah_target }} = {{ $percentage_dibawah_target }}%
   </td>
  </tr>
  <tr>
   <td colspan="4" style="font-weight:bold">
    Target > {{ config('critical-report.target_tat_minutes', 30) }} Menit : {{ $count_tat_diatas_target }} = {{ $percentage_diatas_target }}%
   </td>
  </tr>
 </table>
</body>

</html>
