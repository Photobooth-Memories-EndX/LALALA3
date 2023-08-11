@php
    function getBulan($bln)
    {
        switch ($bln) {
            case 1:
                return 'January';
                break;
            case 2:
                return 'February';
                break;
            case 3:
                return 'March';
                break;
            case 4:
                return 'April';
                break;
            case 5:
                return 'May';
                break;
            case 6:
                return 'June';
                break;
            case 7:
                return 'July';
                break;
            case 8:
                return 'August';
                break;
            case 9:
                return 'September';
                break;
            case 10:
                return 'October';
                break;
            case 11:
                return 'November';
                break;
            case 12:
                return 'December';
                break;
        }
    }
    function tgl($tgl)
    {
        $tanggal = substr($tgl, 8, 2);
        $bulan = getBulan(substr($tgl, 5, 2));
        $tahun = substr($tgl, 0, 4);
        $time = substr($tgl, 11, 8);
        return $tanggal . ' ' . $bulan . ' ' . $tahun . ' - ' . $time;
    }
    function tglonly($tgl)
    {
        $tanggal = substr($tgl, 8, 2);
        $bulan = getBulan(substr($tgl, 5, 2));
        $tahun = substr($tgl, 0, 4);
        $time = substr($tgl, 11, 8);
        return $tanggal . ' ' . $bulan . ' ' . $tahun;
    }
@endphp
