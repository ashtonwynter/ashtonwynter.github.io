<?php
$Url = filter_var('53345.xml', FILTER_SANITIZE_URL);
function getXmlFeed($url) {
    $tempFile = tempnam(sys_get_temp_dir(), 'feed');
    $fileContents = file_get_contents($url);
    if ($fileContents === false) {
        return false;
    }
    file_put_contents($tempFile, $fileContents);
    $gz = gzopen($tempFile, 'rb');
    $xmlString = '';
    if ($gz) {
        while (!gzeof($gz)) {
            $xmlString .= gzread($gz, 4096);
        }
        gzclose($gz);
    } else {
        unlink($tempFile);
        return false;
    }
    unlink($tempFile);
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlString);
    if ($xml === false) {
        return false;
    }
    return $xml;
}

$xmlFeed = getXmlFeed($Url);

$itemsPerPage = 20;
$page = isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Extract items from feed
$allItems = [];
if ($xmlFeed !== false && isset($xmlFeed->channel->item)) {
    foreach ($xmlFeed->channel->item as $item) {
        $namespaces = $item->getNameSpaces(true);
        $g = $item->children($namespaces['g']);
        $availability = (string)$g->availability;
        if ($availability !== 'in_stock') {
            continue; // Only include in_stock items
        }
        $title = (string)$g->title;
        $description = (string)$g->description;
        $brand = (string)$g->brand;

        // If search is set, apply case-insensitive search on title, description or brand
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            if (mb_stripos($title, $search) === false &&
                mb_stripos($description, $search) === false &&
                mb_stripos($brand, $search) === false) {
                continue; // Skip items that don't match search
            }
        }

        $allItems[] = [
            'title' => htmlspecialchars($title),
            'description' => htmlspecialchars($description),
            'brand' => htmlspecialchars($brand),
            'price' => htmlspecialchars((string)$g->price),
            'availability' => htmlspecialchars($availability),
            'image' => filter_var((string)$g->image_link, FILTER_SANITIZE_URL),
            'link' => filter_var((string)$item->link, FILTER_SANITIZE_URL),
        ];
    }
}

$totalItems = count($allItems);
$totalPages = ceil($totalItems / $itemsPerPage);
if ($page > $totalPages) {
    $page = $totalPages > 0 ? $totalPages : 1;
}

$startIndex = ($page - 1) * $itemsPerPage;
$itemsToShow = array_slice($allItems, $startIndex, $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hinged.Digital Live Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            max-width: 996px;
            margin: 0 auto;
        }
        h1 {
            color: #ed4c95;
        }
        .card-img-top {
            object-fit: contain;
            height: 200px;
            background-color: #fff;
        }
        .card {
            transition: box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
        }
        .text-primary {
            font-size: 1.25rem;
        }
        .mt-auto {
            background-color: #ed4c95;
            border: 0;
        }
        .pagination > li > a, .pagination > li > span {
            cursor: pointer;
        }
        .pagination {
  padding-top:20px;
}
.copy{
    text-align:center;
   padding-top:50px;
}.row {
  margin-top:-46px;  
}
    </style>
</head>
<body>
<div class="container my-4">
    <div id="topban"> 
      <a rel="sponsored" href="https://www.awin1.com/cread.php?s=4082530&v=53345&q=534671&r=2567095">
          <img src="https://www.awin1.com/cshow.php?s=4082530&v=53345&q=534671&r=2567095" border="0" alt="Banner">
      </a>
    </div>

    <h1 class="mb-4"></h1>

    <?php if ($xmlFeed === false): ?>
        <div class="alert alert-danger" role="alert">
            Unable to load or parse the XML feed. Please try again later.
        </div>
    <?php elseif ($totalItems === 0): ?>
        <div class="alert alert-info" role="alert">
            No products found<?php echo $search !== '' ? ' matching your search.' : '.'; ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
            <?php foreach ($itemsToShow as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['image']): ?>
                            <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['title']; ?>" />
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $item['title']; ?></h5>
                            <p class="card-text"><?php echo $item['description']; ?></p>
                            <p><strong>Brand:</strong> <?php echo $item['brand']; ?></p>
                            <p><strong>Price:</strong> <?php echo $item['price']; ?></p>
                            <p><strong>Availability:</strong> <?php echo $item['availability']; ?></p>
                            <a href="<?php echo $item['link']; ?>" target="_blank" class="btn btn-primary mt-auto">View Product</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous page link -->
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(['page' => $page - 1, 'search' => $search]); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <?php
                // Display page numbers with a window of +/-2 pages around current page for readability
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(['page'=>1, 'search'=>$search]).'">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $active = ($i === $page) ? ' active' : '';
                    echo '<li class="page-item'.$active.'"><a class="page-link" href="?'.http_build_query(['page'=>$i, 'search'=>$search]).'">'.$i.'</a></li>';
                }
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(['page'=>$totalPages, 'search'=>$search]).'">'.$totalPages.'</a></li>';
                }
                ?>

                <!-- Next page link -->
                <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(['page' => $page + 1, 'search' => $search]); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>   
        </nav>
 <div class="copy">Live XML feed Parser: with pagination and a neatly displayed Bootstrap cards of products. © Copyright 2025 Hinged.Digital </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS Bundle (Popper + Bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
