<?php
/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('classes/cache.php');
require_once('classes/blog_manager.class.php');

/**
 * usage: BlogPostCache::getInstance()->getBlogPost($id);
 */
class BlogPostCache extends Cache {

   /**
    * The singleton pattern
    * @static
    * @return BlogPostCache
    */
   public static function getInstance() {
      return parent::getInstance(__CLASS__);
   }

   /**
    * Get BlogPost class instance
    * @param int $id The blog post id
    * @return Command The blog post attached to the id
    */
   public function getBlogPost($id) {
      return parent::get($id);
   }

   /**
    * Create BlogPost
    * @abstract
    * @param int $id The id
    * @return BlogPost The object
    */
   protected function create($id) {
      return new BlogPost($id);
   }

}

?>
