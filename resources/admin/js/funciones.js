function delete_image(e) {
	arr = this.id.split("-");
	iditem = arr[0]; model = arr[1]; field = arr[2]; id = arr[3]; tmp_upload = arr[4];

	$("#loader").load("/ajax/" + model + '/files/destroy/' + id + "/" + field + "?tmp_upload=" + tmp_upload, function() {
		$('#container-files-' + field).load('/ajax/' + model + '/files/read/' + iditem + '/'  + field + '/?tmp_upload=' + tmp_upload);
	});
	return false;
}
