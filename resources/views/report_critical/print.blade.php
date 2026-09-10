<!DOCTYPE html>
<html>

<head>
    <title></title>
    <style type="text/css">
        body {
            font-family: "Arial", sans-serif;
            font-size: 13px;
        }

        #header {
            margin-top: 0px
        }

        p {
            margin: 0;
        }

        table {
            width: 100%;
            margin: 0 auto;
        }

        tr th {
            background: #eee;
            border: 1px solid;
        }

        .border-bottom {
            border-bottom: 1px solid black;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }
        }

        caption {
            text-align: left;
        }

        img {
            margin-top: 10px;
            margin-left: 40px;
            height: 80px;
            width: 80px;
        }

        .content-width {
            padding-right: 45px;
        }

        .footer-width {
            padding-right: 100px;
        }

        .footer-bottom {
            padding-bottom: 125px;
        }

        #header {
            margin-bottom: 10px;
            margin-top: 0px
        }
    </style>
</head>

<body>

    <div>
        <h3>LAPORAN NILAI KRITIS LABORATORIUM</h3>
        <table>
            <tr>
                <td width="15%">Nama Institusi</td>
                <td width="1%"> : </td>
                <td width="84%"> {{ config('critical-report.institution_name') }}</td>
            </tr>
            <tr>
                <td width="15%">Periode Pemeriksaan</td>
                <td width="1%"> : </td>
                <td width="84%"> {{ $startDate }} - {{ $endDate }} </td>
            </tr>
        </table>

        <br>

        <table id="tb_result" style="border: 1px solid black; margin: 5px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="border-bottom" style="text-align: center;">No</th>
                    <th class="border-bottom" style="text-align: center;">Tanggal</th>
                    <th class="border-bottom" style="text-align: center;">No Lab</th>
                    <th class="border-bottom" style="text-align: center;">No MR</th>
                    <th class="border-bottom" style="text-align: center;">Nama Pasien</th>
                    <th class="border-bottom" style="text-align: center;">Asal Ruangan</th>
                    <th class="border-bottom" style="text-align: center;">Pemeriksaan</th>
                    <th class="border-bottom" style="text-align: center;">Hasil</th>
                    <th class="border-bottom" style="text-align: center;">Nilai Normal</th>
                    <th class="border-bottom" style="text-align: center;">Jam Val</th>
                    <th class="border-bottom" style="text-align: center;">Jam Lapor</th>
                    <th class="border-bottom" style="text-align: center;">TAT Lapor</th>
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
                    // Hitung manual (bukan gmdate) supaya TAT >= 24 jam tidak "wrap around" ke 00:xx:xx
                    $tat_jam = floor($tat_in_seconds / 3600);
                    $tat_menit = floor(($tat_in_seconds % 3600) / 60);
                    $tat_detik = $tat_in_seconds % 60;
                    $tat_time = sprintf('%02d:%02d:%02d', $tat_jam, $tat_menit, $tat_detik);
                } else {
                    // Data tidak wajar: validate_time lebih awal dari report_time.
                    // Tandai daripada menampilkan angka yang menyesatkan, dan jangan
                    // ikut dihitung ke rata-rata/target di bawah.
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
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $tat_time }}</td>
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
        @php
        // Guard supaya tidak division-by-zero saat data kosong
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
                <td width="20%" style="font-weight:bold">Rata-rata TAT Lapor</td>
                <td width="2%" style="font-weight:bold">:</td>
                <td width="20" style="font-weight:bold">{{ $jam }} Jam {{ $menit }} Menit {{ $detik }} Detik</td>
            </tr>
            <tr>
                <td width="20%" style="font-weight:bold">Target &lt;= {{ config('critical-report.target_tat_minutes', 30) }} Menit</td>
                <td width="2%" style="font-weight:bold">:</td>
                <td width="20" style="font-weight:bold">{{ $count_tat_dibawah_target }} = {{ $percentage_dibawah_target }}%</td>
            </tr>
            <tr>
                <td width="20%" style="font-weight:bold">Target &gt; {{ config('critical-report.target_tat_minutes', 30) }} Menit</td>
                <td width="2%" style="font-weight:bold">:</td>
                <td width="20" style="font-weight:bold">{{ $count_tat_diatas_target }} = {{ $percentage_diatas_target }}%</td>
            </tr>
        </table>
</body>

</html>
