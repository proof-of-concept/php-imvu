<?php
/**
* IMVU Avatar Operations
*
* Usage:
*
* Login into your account with:
*   $avatar = new ImvuAvatar('guest_testuser', 'password1234');
*
* Add a buddy with / sends friend request:
*   $avatar->addFriend('otheruser');
*
* Retrieves message list from your account (only message shown at http://www.imvu.com/catalog/web_messagebox.php)
* It probably necessary to disable badges for your account in your account settings!
*   $messages = $avatar->inbox();
*
* $message contains an array with array values like:
*   array(
*     'av' => 'someusername',
*     'time' => 'Tuesday 26th 2013f February 2013 06:14:10 AM',
*     'msg' => 'a stupid message from that user'
*   );
*/
class ImvuAvatar {

  /**
  * Stores session for request that rely on an open session
  * @var string
  */
  private $_session = NULL;

  /**
  * Creates session from logged in imvu user
  * @param string $username
  * @param string $password
  */
  public function __construct($username, $password) {
    $this->_session = tempnam('/tmp', 'CURLCOOKIE');
    $first = $this->_requestUrl('http://www.imvu.com/login');
    $second = $this->_requestUrl(
      'https://secure.imvu.com/login/login/',
      TRUE,
      'http://www.imvu.com/login',
      sprintf('sauce=&avatarname=%s&password=%s&password_strength=&sendto=',
        urlencode($username),
        urlencode($password)
      )
    );
  }

  /**
  * Performs a request using existing session if exists
  * @param string $url
  * @param boolean $post Is this a post request?
  * @param string $ref Referrer for request
  * @param array $postVars Post variables when $post = TRUE
  * @return string Website content (html)
  */
  private function _requestUrl($url, $post = FALSE, $ref = '', $postVars = array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, $post);
    if (!empty($ref)) {
      curl_setopt($ch, CURLOPT_REFERER, $ref);
    }
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Charset:ISO-8859-1,utf-8;q=0.7,*;q=0.7',
      'Accept-Language:en-us;q=0.5,en;q=0.3',
    ));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    if (!empty($postVars)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if (!empty($this->_session)) {
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_session);
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_session);
    }
    $response = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    return $response;
  }

  /**
  * Performs simple regexp on given html
  * @param string html
  * @param string regexp search pattern
  * @return string
  */
  private function _getRegexpString($html, $regexp) {
    preg_match('/'.$regexp.'/m', $html, $m);
    return $m[1];
  }

  /**
  * "Sends a friend request" to the given avatarname
  * @param string $avatarName "target" avatar name
  */
  public function addFriend($avatarName) {
    $result = FALSE;
    $html = $this->_requestUrl('http://www.imvu.com/catalog/web_add_contact.php?contact='.$avatarName);
    if (!empty($html)) {
      $sauce = $this->_getRegexpString($html, '<input type=\'hidden\' name=\'sauce\' value=\'([^\']+)\'');
      if (!empty($sauce)) {
        $response = $this->_requestUrl(
          'http://www.imvu.com/catalog/web_add_contact.php',
          TRUE,
          'http://www.imvu.com/catalog/web_add_contact.php?contact='.$av,
          sprintf('sauce=%s&contact=%s&Add+Friend=Add+Friend',
            urlencode($sauce),
            urlencode($avatarName)
          )
        );
        $result = !empty($response);
      }
    }
    return $result;
  }

  /**
  * Retrieves imvu messages from your inbox (limited amount)
  */
  public function inbox() {
    $result = array();
    $mailHtml = $this->_requestUrl('http://www.imvu.com/catalog/web_messagebox.php');
    if (!empty($mailHtml)) {
      $lines = explode("\n", $mailHtml);
      $msgArray = array();
      $msgArrayKey = -1;
      foreach ($lines as $ln) {
        if (trim($ln) == '<div class="msgWrap">') {
          $msgArrayKey++;
          if ($msgArrayKey >= 10) {
            break;
          }
        }
        if ($msgArrayKey >= 0) {
          if (!isset($msgArray[$msgArrayKey])) {
            $msgArray[$msgArrayKey] = '';
          }
          $msgArray[$msgArrayKey] .= $ln;
        }
      }
      foreach ($msgArray as $msgHtml) {
        $result[] = array(
          'av' => $this->_getRegexpString($msgHtml, 'a class=\'av_name\'(?:[^>]+)>([^<]+)<'),
          'time' => $this->_getRegexpString($msgHtml, '<span class="msgAvNam"><a title="([^"]+)"'),
          'msg' => $this->_getRegexpString($msgHtml, 'style=\'float:right\'><\/span>([^<]+)<'),
        );
      }
    }
    return $result;
  }

}
