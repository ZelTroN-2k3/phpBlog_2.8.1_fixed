<?php
include "core.php";
head();

// Obtenir l'instance de HTML Purifier
$purifier = get_purifier();
?>
	<div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><i class="fa fa-images"></i> Gallery</div>
                <div class="card-body">

					<nav class="mb-3">
						<div class="nav nav-pills nav-fill" id="nav-tab" role="tablist">
							<button class="nav-link active" id="nav-all-tab" data-bs-toggle="tab" data-bs-target="#nav-all" type="button" role="tab" aria-controls="nav-all" aria-selected="true">
								<i class="fas fa-border-all"></i> All
							</button>
<?php
$runalb = mysqli_query($connect, "SELECT * FROM `albums` ORDER BY id DESC");
while ($rowalb = mysqli_fetch_assoc($runalb)) {
	echo '<button class="nav-link" id="nav-' . $rowalb['id'] . '-tab" data-bs-toggle="tab" data-bs-target="#nav-' . $rowalb['id'] . '" type="button" role="tab" aria-controls="nav-' . $rowalb['id'] . '" aria-selected="false">
		      <i class="fas fa-folder"></i> ' . htmlspecialchars($rowalb['title']) . '
		  </button>';
}
?>
						</div>
					</nav>
					
					<div class="tab-content" id="nav-tabContent">
						
						<div class="tab-pane fade show active" id="nav-all" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
							<br />
							<div class="row">
<?php
// Home tab - Prepared Query
$stmt_all = mysqli_prepare($connect, "SELECT * FROM `gallery` WHERE active='Yes' ORDER BY id DESC");
mysqli_stmt_execute($stmt_all);
$run = mysqli_stmt_get_result($stmt_all);
$count = mysqli_num_rows($run);
if ($count <= 0) {
    echo '<div class="alert alert-info">There are no added images.</div>';
} else {
    while ($row = mysqli_fetch_assoc($run)) {
        echo '
		
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100 cursor-pointer" data-bs-toggle="modal" data-bs-target="#p' . $row['id'] . '">
                    <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;" class="card-img-top">

                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-primary">' . htmlspecialchars($row['title']) . '</h6>
                        <div class="mt-auto">
                            <button type="button" data-bs-toggle="modal" data-bs-target="#p' . $row['id'] . '" class="btn btn-sm btn-outline-primary col-12">
                                <i class="fas fa-info-circle"></i> Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
		';
    }
}
mysqli_stmt_close($stmt_all);
?>
							</div>
						</div>
						
						<?php
$runalb = mysqli_query($connect, "SELECT * FROM `albums` ORDER BY id DESC");
while ($rowalb = mysqli_fetch_assoc($runalb)) {
	echo '<div class="tab-pane fade" id="nav-' . $rowalb['id'] . '" role="tabpanel" aria-labelledby="nav-' . $rowalb['id'] . '-tab">
		      <br />
			  <div class="row">';

    // Album tabs - Prepared Query
    $stmt_album = mysqli_prepare($connect, "SELECT * FROM `gallery` WHERE active='Yes' AND album_id=? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt_album, "i", $rowalb['id']);
    mysqli_stmt_execute($stmt_album);
	$run = mysqli_stmt_get_result($stmt_album);
	$count = mysqli_num_rows($run);
	if ($count <= 0) {
		echo '<div class="alert alert-info">There are no images in this album.</div>';
	} else {
		while ($row = mysqli_fetch_assoc($run)) {
			echo '
				<div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 cursor-pointer" data-bs-toggle="modal" data-bs-target="#p' . $row['id'] . '">
						<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;" class="card-img-top">

						<div class="card-body d-flex flex-column">
							<h6 class="card-title text-primary">' . htmlspecialchars($row['title']) . '</h6>
							<div class="mt-auto">
								<button type="button" data-bs-toggle="modal" data-bs-target="#p' . $row['id'] . '" class="btn btn-sm btn-outline-primary col-12">
									<i class="fas fa-info-circle"></i> Details
								</button>
							</div>
						</div>
					</div>
				</div>
	';
		}
	}
    mysqli_stmt_close($stmt_album);

	echo '</div></div>';
}

// Modals - Prepared Query
$stmt_modals = mysqli_prepare($connect, "SELECT * FROM `gallery` WHERE active='Yes' ORDER BY id DESC");
mysqli_stmt_execute($stmt_modals);
$runimg = mysqli_stmt_get_result($stmt_modals);
$countimg = mysqli_num_rows($runimg);
if ($countimg > 0) {
	while ($rowimg = mysqli_fetch_assoc($runimg)) {
		echo '
			<div class="modal fade" id="p' . $rowimg['id'] . '" tabindex="-1" aria-labelledby="modalTitle' . $rowimg['id'] . '" aria-hidden="true">
				<div class="modal-dialog modal-lg modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header bg-primary text-white">
							<h5 class="modal-title" id="modalTitle' . $rowimg['id'] . '">' . htmlspecialchars($rowimg['title']) . '</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<img src="' . htmlspecialchars($rowimg['image']) . '" width="100%" height="auto" alt="' . htmlspecialchars($rowimg['title']) . '" class="img-fluid rounded" /><br /><br />
							' . $purifier->purify($rowimg['description']) . '
						</div>
					</div>
				</div>
			</div>
		';
	}
}
mysqli_stmt_close($stmt_modals);
?>
						
						
					</div>
					
                </div>
            
        </div>
	</div>
</div></div>
<?php
// La fonction footer() contient la balise </body> et </html>
footer();
?>