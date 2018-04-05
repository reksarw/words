<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

$app->group('/jokes', function() use ($app){

	$app->get('/', function(Request $request, Response $response) {
		$getdata = $request->getQueryParams();
		$perpage = 5;
		$offset = (is_numeric($getdata['page']) && $getdata['page'] != 1 ? ($getdata['page'] - 1) * $perpage : 0);
		$sql = "SELECT id,uid,title,image_url,created_at FROM t_jokes WHERE status = 1 ORDER BY created_at DESC LIMIT $offset, $perpage";
	  $stmt = $this->db->prepare($sql);
	  $stmt->execute();
	  $row = $stmt->rowCount();
		
		$result = [
			'is_ok' => $row > 0 ? true : false,
			$row > 0 ? 'data' : 'error_message' =>
			$row > 0 ? $stmt->fetchAll() : 'Data jokes masih kosong!'
		];

		if($row == 5) {
			$result['next_url'] = $this->baseUrl.'v1/jokes/?key='.$getdata['key'].'&page='.($offset == 0 ? 2 : $getdata['page'] + 1);
		}

		return $response->withJson($result, 200);
	});

	$app->get('/random', function(Request $request, Response $response){
		$getdata = $request->getQueryParams();
		$sql = "SELECT id,uid,title,image_url,created_at FROM t_jokes WHERE status = 1 ORDER BY RAND()";
		$sql.= is_numeric($getdata['limit']) ? " LIMIT ".$getdata['limit'] : " LIMIT 1";
		$stmt = $this->db->prepare($sql);
	  $stmt->execute();
	  $row = $stmt->rowCount();
		
		$result = [
			'is_ok' => $row > 0 ? true : false,
			$row > 0 ? 'data' : 'error_message' =>
			$row > 0 ? (is_numeric($getdata['limit']) && $getdata['limit'] > 1 ? $stmt->fetchAll() : $stmt->fetch()) : 'Data jokes masih kosong!'
		];

		return $response->withJson($result, 200);
	});

	$app->post('/add', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();
		$uploadedFiles = $request->getUploadedFiles();

		$required = ['title', 'id_category'];
		$error = false;
		foreach($postdata as $key => $params) {
			if(!in_array($key,$required)) $error = true;
		}

		// handle single input with single file upload
    $uploadedFile = $uploadedFiles['image'];
    if($uploadedFile == null || $error) $result = ['is_ok' => false, 'error_message' => 'Parameter masih ada yang kosong'];
    else if($uploadedFile->getError() === UPLOAD_ERR_OK) {
    	$acceptedExt = array('jpg','png','gif','mp4');
			$extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    	if( ! in_array($extension, $acceptedExt)) $result = ['is_ok' => false, 'error_message' => 'Maaf, ektensi tidak diperbolehkan!'];
    	else {
	    	$basename = sha1($uploadedFile->getClientFilename());
	      $filename = sprintf('%s.%0.8s', $basename, $extension);
	      $month = date('m');
	      $directory = $this->get('settings')['upload_directory'] . DIRECTORY_SEPARATOR . $month;
	      if(!is_dir($directory)) mkdir($directory, 0744);
	      $uploadedFile->moveTo($directory. DIRECTORY_SEPARATOR . $filename);
	      
	      $sql = "INSERT INTO t_jokes (id_category,title,image_url) VALUES (:category, :title, :imageUrl)";
				$stmt = $this->db->prepare($sql);

				$data = [
					':category' => $postdata['id_category'],
					':title' => $postdata['title'],
					':imageUrl' => $this->baseUrl."resources/uploads/{$month}/".$filename
				];

				$exec = $stmt->execute($data);

	      $result = ['is_ok' => true, 'message' => 'Berhasil menambah jokes'];
    	}
    }

    return $response->withJson($result, 200);
	});

	$app->post('/delete/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];

		$sql = "SELECT uid FROM t_jokes WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0) {
			$sql = "UPDATE t_jokes SET status = 0 WHERE uid = :uid";
			$stmt	= $this->db->prepare($sql);
			$stmt->execute([':uid' => $uid]);
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil menghapus jokes!' : 'Jokes tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});

	$app->post('/updateImage/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];
		$uploadedFiles = $request->getUploadedFiles();

		// handle single input with single file upload
    $uploadedFile = $uploadedFiles['image'];
    if($uploadedFile == null) $result = ['is_ok' => false, 'error_message' => 'Gambar belum dipilih!'];
    else if($uploadedFile->getError() === UPLOAD_ERR_OK) {
    	$acceptedExt = array('jpg','png','gif','mp4');
			$extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    	if( ! in_array($extension, $acceptedExt)) $result = ['is_ok' => false, 'error_message' => 'Maaf, ektensi tidak diperbolehkan!'];
    	else {
	    	$basename = sha1($uploadedFile->getClientFilename());
	      $filename = sprintf('%s.%0.8s', $basename, $extension);
	      $month = date('m');
	      $directory = $this->get('settings')['upload_directory'] . DIRECTORY_SEPARATOR . $month;
	      if(!is_dir($directory)) mkdir($directory, 0744);
	      $uploadedFile->moveTo($directory. DIRECTORY_SEPARATOR . $filename);
	      
	      $sql = "UPDATE t_jokes SET image_url = :imageUrl WHERE uid = :uid";
				$stmt = $this->db->prepare($sql);

				$data = [
					':uid' => $uid,
					':imageUrl' => $this->baseUrl."resources/uploads/{$month}/".$filename
				];

				$exec = $stmt->execute($data);

	      $result = ['is_ok' => true, 'message' => 'Berhasil mengubah gambar jokes'];
    	}
    }

    return $response->withJson($result);
	});
	$app->post('/update/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];
		$postdata = $request->getParsedBody();

		$sql = "SELECT uid FROM t_jokes WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0) {
			$required = ['title', 'id_category'];
			$havePost = false;
			foreach($postdata as $key => $params) {
				if(in_array($key,$required)) $havePost = true;
			}

			if($havePost) $sql = "UPDATE t_jokes SET";

			if($postdata['title']) {
				$dataUpdate[':title'] = $postdata['title'];
				$sql .= " title = :title";
			}

			if($postdata['id_category']) {
				$dataUpdate[':category'] = $postdata['id_category'];
				$bind = " id_category = :category";  
				$sql .= $postdata['title'] ? ", {$bind}" : $bind;
			}

			if($havePost) {
				$sql .= " WHERE uid = :uid";
				$dataUpdate[':uid'] = $uid;

				$stmt = $this->db->prepare($sql);
				$exec = $stmt->execute($dataUpdate);
			}
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil mengubah kategori jokes!' : 'Kategori jokes tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});

	$app->post('/category/add', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		if(!$postdata['category_name']) $result = ['is_ok' => false, 'error_message' => 'Parameter masih ada yang kosong'];
		else {
			$sql = "INSERT INTO t_jokes_category (category_name) VALUES (:category)";
			$stmt = $this->db->prepare($sql);

			$data = [
				':category' => $postdata['category_name'],
			];

			$exec = $stmt->execute($data);

      $result = ['is_ok' => true, 'message' => 'Berhasil menambah kategori jokes'];
		}

		return $response->withJson($result, 200);
	});

	$app->post('/category/delete/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];

		$sql = "SELECT uid FROM t_jokes_category WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0) {
			$sql = "UPDATE t_jokes_category SET status = 0 WHERE uid = :uid";
			$stmt	= $this->db->prepare($sql);
			$stmt->execute([':uid' => $uid]);
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil menghapus kategori jokes!' : 'Kategori jokes tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});

	$app->post('/category/update/{uid}', function(Request $request, Response $response, $args){
		$postdata = $request->getParsedBody();
		$uid = $args['uid'];

		$sql = "SELECT uid FROM t_jokes_category WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0 && $postdata['category_name']) {
			$sql = "UPDATE t_jokes_category SET category_name = :category WHERE uid = :uid";
			$stmt	= $this->db->prepare($sql);
			$stmt->execute([':uid' => $uid, ':category' => $postdata['category_name']]);
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil mengubah kategori jokes!' : 'Kategori jokes tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});
});