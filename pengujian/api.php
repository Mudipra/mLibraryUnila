<?php
# REST API Web Service
# Meminta data dengan GET: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa dan http://localhost/praktikumweb/api.php/mahasiswa/1
# Membuat data dengan POST: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa menggunakan metode POST. Data nama dan npm harus disertakan
# Mengubah data dengan PUT: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa/1 menggunakan metode PUT. Data nama dan npm harus disertakan
# Menghapus data dengan DELETE: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa/1 menggunakan metode DELETE
# Gunakan aplikasi Postman untuk pengujian API/Web Service | www.getpostman.com
# api.php

# Format Data: JSON
header('Content-type: application/json');

# Mendapatkan method yang digunakan: GET/POST/PUT/DELETE

# Cara 1: Menggunakan variabel $_SERVER
# $method = $_SERVER['REQUEST_METHOD'];

# Cara 2: Menggunakan getenv sehingga tidak perlu bekerja dengan variabel $_SERVER
$method = getenv('REQUEST_METHOD');

# This function is useful (compared to $_SERVER, $_ENV) because it searches $varname key in those array case-insensitive manner.

# Cara 3: Menggunakan hidden input _METHOD, workaround
$method = isset($_REQUEST['_METHOD']) ? $_REQUEST['_METHOD'] : $method;

$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

function process_get($param) {
    if($param[0] == "pinjam") {
        require_once 'dbconfig.php';

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password,
                            array(
                                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                    \PDO::ATTR_PERSISTENT => false
                                )
                           );

            if(!empty($param[1])) {
                $handle = $conn->prepare("
                    SELECT member_name, title, loan_date, return_date, due_date FROM member LEFT JOIN loan ON member.member_id = loan.member_id LEFT JOIN item ON loan.item_code = item.item_code LEFT JOIN biblio ON item.biblio_id = biblio.biblio_id WHERE member.member_id = :id
                ");

                $handle->bindParam(':id', $param[1], PDO::PARAM_INT);

                $handle->execute();
            } else {
                $handle = $conn->query("SELECT id, nama, npm FROM mahasiswa");
            }

            if($handle->rowCount()){
                $status = 'Berhasil';
                $data = $handle->fetchAll(PDO::FETCH_ASSOC);
                $arr = array('status' => $status, 'data' => $data);
            } else {
                $status = "Tidak ada data";
                $arr = array('status' => $status);
            }

            echo json_encode($arr);
        }
        catch (PDOException $pe) {
            die(json_encode($pe->getMessage()));
        }
    }
}

function process_post($param) {
    if((count($param) == 1) and ($param[0] == "mahasiswa")) {
        require_once 'dbconfig.php';

        $dataNama = (isset($_POST['nama']) ? $_POST['nama'] : NULL);
        $dataNPM = (isset($_POST['npm']) ? $_POST['npm'] : NULL);

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password,
                            array(
                                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                    \PDO::ATTR_PERSISTENT => false
                                )
                           );

            $handle = $conn->prepare("
                INSERT INTO mahasiswa (nama, npm)
                VALUES (:nama, :npm)
            ");

            $handle->bindParam(':nama', $dataNama);
            $handle->bindParam(':npm', $dataNPM);

            $handle->execute();

            if($handle->rowCount()){
                $status = 'Berhasil';
                $idTerakhir = $conn->lastInsertId();
                $arr = array('status' => $status, 'id' => $idTerakhir );
            } else {
                $status = "Gagal";
                $arr = array('status' => $status);
            }

            echo json_encode($arr);
        }
        catch (PDOException $pe) {
            die(json_encode($pe->getMessage()));
        }
    }
}

function process_put($param) {
    if((count($param) == 2) and $param[0] == "mahasiswa" and $_SERVER["CONTENT_TYPE"] == 'application/x-www-form-urlencoded') {
        require_once 'dbconfig.php';

        # Mendapatkan nilai yang disematkan pada body PUT, bisa juga untuk POST
        # Data harus dikirimkan dalam body dengan format x-www-form-urlencoded tidak boleh form-data
        parse_str(file_get_contents('php://input'), $data);
        $dataNama = $data['nama'];
        $dataNPM =  $data['npm'];

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password,
                            array(
                                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                    \PDO::ATTR_PERSISTENT => false
                                )
                           );

             $handle = $conn->prepare("
                UPDATE mahasiswa
                SET nama=:nama, npm=:npm, tanggal_tercatat=NOW() WHERE ID=:id
            ");

            $dataID = $param[1];

            $handle->bindParam(':id', $dataID, PDO::PARAM_INT);
            $handle->bindParam(':nama', $dataNama);
            $handle->bindParam(':npm', $dataNPM);

            $handle->execute();

            if($handle->rowCount()){
                $status = 'Berhasil';
            } else {
                $status = "Gagal";
            }
            $arr = array('status' => $status, 'id' => $dataID, 'nama' => $dataNama, 'npm' => $dataNPM);

            echo json_encode($arr);
        }
        catch (PDOException $pe) {
            die(json_encode($pe->getMessage()));
        }
    }
}

function process_delete($param) {
    if((count($param) == 2) and $param[0] == "mahasiswa") {
        require_once 'dbconfig.php';

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password,
                            array(
                                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                    \PDO::ATTR_PERSISTENT => false
                                )
                           );

             $handle = $conn->prepare("
                DELETE FROM mahasiswa
                WHERE ID=:id
            ");

            $dataID = $param[1];

            $handle->bindParam(':id', $dataID, PDO::PARAM_INT);

            $handle->execute();

            if($handle->rowCount()){
                $status = 'Berhasil';
            } else {
                $status = "Gagal";
            }

            $arr = array('status' => $status, 'id' => $dataID );

            echo json_encode($arr);
        }
        catch (PDOException $pe) {
            die(json_encode($pe->getMessage()));
        }
    }
}

switch ($method) {
    case 'PUT':
        process_put($request);
        break;
    case 'POST':
        process_post($request);
        break;
    case 'GET':
        process_get($request);
        break;
    case 'HEAD':
        process_head($request);
        break;
    case 'DELETE':
        process_delete($request);
        break;
    case 'OPTIONS':
        process_options($request);
        break;
    default:
        handle_error($request);
        break;
}
# Gunakan aplikasi Postman untuk pengujian API/Web Service | www.getpostman.com
# Meminta data dengan GET: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa dan http://localhost/praktikumweb/api.php/mahasiswa/1
# Membuat data dengan POST: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa menggunakan metode POST. Data nama dan npm harus disertakan
# Memutakhirkan data dengan PUT: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa/1 menggunakan metode PUT. Data nama dan npm harus disertakan
# Menghapus data dengan DELETE: Uji dengan membuka http://localhost/praktikumweb/api/mahasiswa/1 menggunakan metode DELETE
?>
