<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/quotes', function() use ($app){

	$app->get('/', function(Request $request, Response $response) {
		$getdata = $request->getQueryParams();
		$perpage = 5;
		$offset = (is_numeric($getdata['page']) && $getdata['page'] != 1 ? ($getdata['page'] - 1) * $perpage : 0);
		$sql = "SELECT id,uid,author,text,created_at FROM t_quotes WHERE status = 1 ORDER BY created_at DESC LIMIT $offset, $perpage";
	  $stmt = $this->db->prepare($sql);
	  $stmt->execute();
	  $row = $stmt->rowCount();
		
		$result = [
			'is_ok' => $row > 0 ? true : false,
			$row > 0 ? 'data' : 'error_message' =>
			$row > 0 ? $stmt->fetchAll() : 'Data quotes masih kosong!'
		];

		if($row == 5) {
			$result['next_url'] = $this->baseUrl.'v1/quotes/?key='.$getdata['key'].'&page='.($offset == 0 ? 2 : $getdata['page'] + 1);
		}

		return $response->withJson($result, 200);
	});

	$app->get('/random', function(Request $request, Response $response){
		$getdata = $request->getQueryParams();
		$sql = "SELECT uid,author,text,created_at FROM t_quotes WHERE status = 1 ORDER BY RAND() LIMIT 1";
		$stmt = $this->db->prepare($sql);
	  $stmt->execute();
	  $row = $stmt->rowCount();
		
		$result = [
			'is_ok' => $row > 0 ? true : false,
			$row > 0 ? 'data' : 'error_message' =>
			$row > 0 ? $stmt->fetch() : 'Data quotes masih kosong!'
		];

		return $response->withJson($result, 200);
	});

	$app->get('/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];
		$sql = "SELECT author,text,created_at FROM t_quotes WHERE status = 1 AND uid = :uid";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		$result = [
			'is_ok' => $row > 0 ? true : false,
			$row > 0 ? 'data' : 'error_message' =>
			$row > 0 ? $stmt->fetch() : 'Data quote tidak ditemukan!'
		];

		return $response->withJson($result);
	});

	$app->post('/add', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();
	
		$required = ['author', 'text'];
		$error = false;
		foreach($postdata as $key => $params) {
			if(!in_array($key,$required)) $error = true;
		}

		if(!$error) {
			$sql = "INSERT INTO t_quotes (author,text) VALUES (:author, :text)";
			$stmt = $this->db->prepare($sql);

			$data = [
				':author' => $postdata['author'],
				':text' => $postdata['text']
			];

			$exec = $stmt->execute($data);
		}

		$result = [
			'is_ok' => ! $error ? true : false,
			'message' => ! $error ? 'Berhasil menambah quote!' : 'Parameter masih ada yang kosong',
		];

		return $response->withJson($result, 200);
	});

	$app->post('/delete/{uid}', function(Request $request, Response $response, $args){
		$uid = $args['uid'];

		$sql = "SELECT uid FROM t_quotes WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0) {
			$sql = "UPDATE t_quotes SET status = 0 WHERE uid = :uid";
			$stmt	= $this->db->prepare($sql);
			$stmt->execute([':uid' => $uid]);
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil menghapus quote!' : 'Quote tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});

	$app->post('/update/{uid}', function(Request $request, Response $response, $args){
		$postdata = $request->getParsedBody();
		$uid = $args['uid'];

		$sql = "SELECT author, text FROM t_quotes WHERE uid = :uid AND status = 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$row = $stmt->rowCount();

		if($row > 0) {
			$data = $stmt->fetch();
			$dataUpdate = [
				':author' => isset($postdata['author']) ? $postdata['author'] : $data['author'],
				':text' => isset($postdata['text']) ? $postdata['text'] : $data['text'],
				':uid' => $uid
			];
			$sql = "UPDATE t_quotes SET author = :author , text = :text WHERE uid = :uid";
			$stmt	= $this->db->prepare($sql);
			$stmt->execute($dataUpdate);
		}

		$result = [
			'is_ok' => $row > 0 ? true : false,
			'message' => $row > 0 ? 'Berhasil mengubah quote!' : 'Quote tidak ditemukan!'
		];

		return $response->withJson($result, 200);
	});

});
