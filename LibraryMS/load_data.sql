-- Run these before the query:
SET GLOBAL net_read_timeout = 600;
SET GLOBAL net_write_timeout = 600;
SET GLOBAL max_allowed_packet = 1073741824; -- 1 GB
SET FOREIGN_KEY_CHECKS = 0;

SET @row_number = 0;

-- Ensure categories exist
INSERT IGNORE INTO libraryms2.Book_Categories (CategoryName) VALUES
('Young Adult'),
('Classics'),
('Children'),
('Fantasy'),
('Historical'),
('Science Fiction'),
('Romance'),
('Mystery'),
('Non-Fiction');

-- Load data (without ON DUPLICATE KEY UPDATE)
LOAD DATA INFILE 'C:\\ProgramData\\MySQL\\MySQL Server 8.0\\Uploads\\data.csv'
INTO TABLE libraryms2.Book
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"' 
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(@bookid, @title, @series, @author, @rating, @description, @language, @isbn, @genres,
 @characters, @bookFormat, @edition, @pages, @publisher, @publishDate, @firstPublishDate,
 @awards, @numRatings, @ratingsByStars, @likedPercent, @setting, @coverImg, @bbeScore,
 @bbeVotes, @price)
SET 
    ISBN = LEFT(IF(@isbn IS NOT NULL AND @isbn != '' AND @isbn REGEXP '^[0-9]{10,13}$', @isbn, 
            CONCAT('TEMP-', LPAD(@row_number := @row_number + 1, 8, '0'))), 13),
    Title = @title,
    Author = @author,
    Publisher = @publisher,
    PublicationYear = IF(@publishDate REGEXP '^[0-1][0-9]/[0-3][0-9]/[0-9]{4}$', YEAR(STR_TO_DATE(@publishDate, '%m/%d/%Y')), NULL),
    CategoryID = (SELECT CategoryID FROM libraryms2.Book_Categories WHERE CategoryName = SUBSTRING_INDEX(@genres, ' | ', 1)),
    StatusID = (SELECT StatusID FROM libraryms2.Book_Status WHERE StatusName = 'Available'),
    CoverImage = @coverImg,
    Summary = @description,
    Pages = IF(@pages REGEXP '^[0-9]+$', NULLIF(@pages, '') + 0, NULL),
    AverageRating = IF(@rating REGEXP '^[0-9]+\\.?[0-9]*$', NULLIF(@rating, '') + 0, NULL),
    TotalReviews = IF(@numRatings REGEXP '^[0-9]+$', NULLIF(@numRatings, '') + 0, NULL),
    CreatedDate = NOW(),
    UpdatedDate = NOW();

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'تم تحميل الداتا بنجاح!' as Status;
