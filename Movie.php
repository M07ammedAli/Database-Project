<?php

include_once(__DIR__ . "/DBconn.php");
define('BASE_URL', '/~u202304108/MovieReview');

class Movie
{
    private $movie_id;
    private $title;
    private $description;
    private $category_id;
    private $creator_id;
    private $poster_image;
    private $trailer_url;
    private $release_date;
    private $status;
    private $view_count;
    private $created_at;

   
    public function getMovieId()     { return $this->movie_id; }
    public function getTitle()       { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getCategoryId()  { return $this->category_id; }
    public function getCreatorId()   { return $this->creator_id; }
    public function getPosterImage() { return $this->poster_image; }
    public function getTrailerUrl()  { return $this->trailer_url; }
    public function getReleaseDate() { return $this->release_date; }
    public function getStatus()      { return $this->status; }
    public function getViewCount()   { return $this->view_count; }
    public function getCreatedAt()   { return $this->created_at; }


    public function setMovieId($v)     { $this->movie_id = $v; }
    public function setTitle($v)       { $this->title = $v; }
    public function setDescription($v) { $this->description = $v; }
    public function setCategoryId($v)  { $this->category_id = $v; }
    public function setCreatorId($v)   { $this->creator_id = $v; }
    public function setPosterImage($v) { $this->poster_image = $v; }
    public function setTrailerUrl($v)  { $this->trailer_url = $v; }
    public function setReleaseDate($v) { $this->release_date = $v; }
    public function setStatus($v)      { $this->status = $v; }

 
    public function cleanXSS($data)
    {
        return htmlentities(strip_tags(trim($data)));
    }

  
    public function initWithId($id)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT movie_id, title, description, category_id, creator_id,
                    poster_image, trailer_url, release_date, status, view_count, created_at
             FROM dbProj_movies WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $mid, $title, $desc, $cat, $creator,
                                $poster, $trailer, $rdate, $status, $views, $created);
        if (mysqli_stmt_fetch($stmt)) {
            $this->movie_id     = $mid;
            $this->title        = $title;
            $this->description  = $desc;
            $this->category_id  = $cat;
            $this->creator_id   = $creator;
            $this->poster_image = $poster;
            $this->trailer_url  = $trailer;
            $this->release_date = $rdate;
            $this->status       = $status;
            $this->view_count   = $views;
            $this->created_at   = $created;
            mysqli_stmt_close($stmt);
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }


    public function save()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "INSERT INTO dbProj_movies
                (title, description, category_id, creator_id, poster_image,
                 trailer_url, release_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // s s i i s s s s
        mysqli_stmt_bind_param($stmt, "ssiissss",
            $this->title, $this->description, $this->category_id, $this->creator_id,
            $this->poster_image, $this->trailer_url, $this->release_date, $this->status);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $this->movie_id = mysqli_insert_id($dbc);
        }
        mysqli_stmt_close($stmt);
        return $ok;
    }

  
    public function update()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "UPDATE dbProj_movies
             SET title = ?, description = ?, category_id = ?, poster_image = ?,
                 trailer_url = ?, release_date = ?, status = ?
             WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "ssissssi",
            $this->title, $this->description, $this->category_id, $this->poster_image,
            $this->trailer_url, $this->release_date, $this->status, $this->movie_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

  
    public function delete()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_movies WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->movie_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

  
    public function publish()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "UPDATE dbProj_movies SET status = 'published' WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->movie_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    
    public function incrementViews()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "UPDATE dbProj_movies SET view_count = view_count + 1 WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->movie_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

  
    public function getAverageRating()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT IFNULL(ROUND(AVG(stars),1), 0) FROM dbProj_ratings WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->movie_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $avg);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $avg;
    }

    //  getRatingCount(): number of ratings 
    public function getRatingCount()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT COUNT(*) FROM dbProj_ratings WHERE movie_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->movie_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $cnt);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $cnt;
    }

    //  getUserRating(): the logged-in user's stars for this movie (or null) 
    public function getUserRating($userId)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT stars FROM dbProj_ratings WHERE movie_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $this->movie_id, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $stars);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $found ? (int)$stars : null;
    }

    //  upsertRating(): INSERT or UPDATE a user's rating (UNIQUE on movie+user) 
    public function upsertRating($userId, $stars)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "INSERT INTO dbProj_ratings (movie_id, user_id, stars)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = VALUES(stars)");
        mysqli_stmt_bind_param($stmt, "iii", $this->movie_id, $userId, $stars);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    //  insertComment(): add a new comment 
    public function insertComment($userId, $body)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "INSERT INTO dbProj_comments (movie_id, user_id, body) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $this->movie_id, $userId, $body);
        $ok = mysqli_stmt_execute($stmt);
        $newId = $ok ? mysqli_insert_id($dbc) : 0;
        mysqli_stmt_close($stmt);
        return $newId;
    }

    //  deleteComment(): remove a comment (static, admin use) 
    public static function deleteComment($commentId)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_comments WHERE comment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $commentId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    // =================================================================
    // List / search helpers (return arrays of associative rows)
    // =================================================================

    //  countPublished(): total published movies (for pagination) 
    public static function countPublished()
    {
        $dbc = getConnection();
        $res = mysqli_query($dbc, "SELECT COUNT(*) AS c FROM dbProj_movies WHERE status = 'published'");
        $row = mysqli_fetch_assoc($res);
        return (int)$row['c'];
    }

    //  listPublished(): home page, newest first, with category + avg rating 
    public static function listPublished($limit = 10, $offset = 0)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT m.movie_id, m.title, m.description, m.poster_image, m.trailer_url,
                    m.release_date, m.view_count, m.created_at,
                    c.name AS category,
                    IFNULL(ROUND(AVG(r.stars),1),0) AS avg_rating
             FROM dbProj_movies m
             LEFT JOIN dbProj_categories c ON c.category_id = m.category_id
             LEFT JOIN dbProj_ratings r    ON r.movie_id = m.movie_id
             WHERE m.status = 'published'
             GROUP BY m.movie_id
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        mysqli_stmt_close($stmt);
        return $rows;
    }
    
    //  listMostPopular(): admin panel (list movies based on view count)
    public static function listMostPopular($dateFrom ='', $dateTo='')
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
                "SELECT m.title, m.view_count, m.release_date, u.username AS Creator
                 FROM dbProj_movies m JOIN dbProj_users u ON m.creator_id = u.user_id
                 WHERE m.status = 'published' AND m.release_date BETWEEN ? AND ?
                 ORDER BY m.view_count DESC");
        mysqli_stmt_bind_param($stmt, 'ss', $dateFrom, $dateTo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        
        while($row = mysqli_fetch_assoc($res))
        {
            $rows[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $rows;
    }

    //  callMostPopularProc(): Report 1 via stored procedure 
    public static function callMostPopularProc($dateFrom, $dateTo)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "CALL dbProj_most_popular_in_range(?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    //  callContentByCreatorProc(): Report 2 via stored procedure 
    public static function callContentByCreatorProc($creatorId)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "CALL dbProj_content_by_creator(?)");
        mysqli_stmt_bind_param($stmt, "i", $creatorId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        mysqli_stmt_close($stmt);
        return $rows;
    }
    

    public static function listByCreator($creatorId)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc,
            "SELECT movie_id, title, status, view_count, created_at
             FROM dbProj_movies
             WHERE creator_id = ?
             ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, "i", $creatorId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        mysqli_stmt_close($stmt);
        return $rows;
    }

   
    public static function listAll()
    {
        $dbc = getConnection();
        $sql = "SELECT m.movie_id, m.title, m.status, m.view_count, m.created_at,
                       u.username AS creator
                FROM dbProj_movies m
                LEFT JOIN dbProj_users u ON u.user_id = m.creator_id
                ORDER BY m.created_at DESC";
        $res = mysqli_query($dbc, $sql);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        return $rows;
    }


    public static function fullTextSearch($terms, $creatorId = 0, $dateFrom = '', $dateTo = '', $sort = 'relevance')
    {
        $dbc = getConnection();

        $where  = array("m.status = 'published'");
        $params = array();
        $types  = "";
        $relevanceSelect = "0 AS relevance";

        if (trim($terms) !== "") {
            // FULLTEXT match (Unit 7). Boolean mode is more forgiving for partial words.
            $relevanceSelect = "MATCH(m.title, m.description) AGAINST(? IN BOOLEAN MODE) AS relevance";
            $where[] = "MATCH(m.title, m.description) AGAINST(? IN BOOLEAN MODE)";
            // term used twice: once in SELECT, once in WHERE
            $params[] = $terms; $types .= "s";   // for SELECT relevance
            $params[] = $terms; $types .= "s";   // for WHERE match
        }
        if ($creatorId > 0) {
            $where[] = "m.creator_id = ?";
            $params[] = $creatorId; $types .= "i";
        }
        if (trim($dateFrom) !== "") {
            $where[] = "DATE(m.created_at) >= ?";
            $params[] = $dateFrom; $types .= "s";
        }
        if (trim($dateTo) !== "") {
            $where[] = "DATE(m.created_at) <= ?";
            $params[] = $dateTo; $types .= "s";
        }

        if ($sort === 'newest') {
            $order = "m.created_at DESC";
        } elseif ($sort === 'popular') {
            $order = "m.view_count DESC";
        } else {
            $order = (trim($terms) !== "") ? "relevance DESC" : "m.created_at DESC";
        }

        $sql = "SELECT m.movie_id, m.title, m.description, m.poster_image,
                       m.view_count, m.created_at, c.name AS category,
                       IFNULL(ROUND(AVG(r.stars),1),0) AS avg_rating,
                       $relevanceSelect
                FROM dbProj_movies m
                LEFT JOIN dbProj_categories c ON c.category_id = m.category_id
                LEFT JOIN dbProj_ratings r    ON r.movie_id = m.movie_id
                WHERE " . implode(" AND ", $where) . "
                GROUP BY m.movie_id
                ORDER BY $order
                LIMIT 50";

        $stmt = mysqli_prepare($dbc, $sql);
        if ($types !== "") {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        mysqli_stmt_close($stmt);
        return $rows;
    }

   
    public static function listCreators()
    {
        $dbc = getConnection();
        $res = mysqli_query($dbc,
            "SELECT user_id, username FROM dbProj_users
             WHERE role IN ('creator','admin') ORDER BY username");
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        return $rows;
    }
    

    public static function listCategories()
    {
        $dbc = getConnection();
        $res = mysqli_query($dbc,
            "SELECT category_id, name FROM dbProj_categories ORDER BY name");
        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        return $rows;
    }
}
?>