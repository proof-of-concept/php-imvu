<?php
/**
* IMVU Account Operations
*
* Usage:
*
* $account = new ImvuAccount();
*
* Is account valid? (Means avatar is confirmed, logged in once in 3d client)
*   $account->isValid('someusername'); // returns TRUE or FALSE if not
*
* Retrieve a badges list for the given account. It is necessary that the avatar allows access to their badge panel.
*   $badges $account->badgeList('someusername');
*
* $badges contains an array with array values like:
*    array(
*      'id' => 'badge-1231-1231',
*      'img' => 'http://imvu..usercontent.../someurl/image.png',
*      'name' => 'cool badge name',
*    );
*/
class ImvuAccount {
  
  /**
  * Checks whether an imvu account is valid or not
  * @param string avatar name of account that should be checked
  * @return boolean
  */
	public function isValid($avatarName) {
    return !preg_match('<!-- avatar not ready -->', file_get_contents('http://avatars.imvu.com/'.$avatarName);
  }

  /**
  * Retrieves a list of available badges from the given username. Usually these badges are free to request.
  * @param string avatar name to retrieve list from
  * @return array list of badges
  */
  public function badgeList($avatarName) {
    $result = array();
    $url = 'http://www.imvu-customer-sandbox.com/catalog/web_panel.php?panel=badges_panel&user='.$avatarName;
    $content = file($url);
    $checkFor = "<img id='badge-";
    $matches = array();
    $badges = array();
    foreach ($content as $l => &$line) {
      $line = trim($line);
      if (substr($line, 0, strlen($checkFor)) == $checkFor) {
        preg_match("/id='(.*)' src='(.*)' alt='(.*)' style='(?:.*)'/", $line, $matches);
        if (!in_array($matches[1], $ignore)) {
          $id = $matches[1];
          $src = $matches[2];
          $name = trim(strip_tags($matches[3]));
          $result[] = array(
            'id' => $id,
            'img' => $src,
            'name' => $name,
          );
        }
      }
    }
    return $result;
  }

}