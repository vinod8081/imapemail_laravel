<?php

namespace Codebank\Imapemail;

/**
 * Helper class for imap access
 * @name imap.php
 * @author VP 
 * @version 1.0
 * @package    protocols
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class RenoImap {

    /**
     * imap connection
     */
    public $imap = false;

    /**
     * mailbox url string
     */
    public $mailbox = '';

    /**
     * currentfolder
     */
    public $folder = 'inbox';

    /**
     * Set message per page
     */
    public $message_per_page = 20;

    /**
     * Set the page number
     */
    public $current_page = 1;

    /**
     *
     * Codeigniter object
     */
    public $ci;

    /**
     * initialize imap helper
     *
     * @return void
     * @param $mailbox imap_open string
     * @param $username
     * @param $password
     * @param $encryption ssl or tls
     */
    public function __construct() {
        
    }

    /**
     * This method will connect user to webmail service
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function connectToMailBox($username, $password) {
        try {
            // get imap providers
            //$imap_details = $this->ci->reno_emailconstant->imap_providers_details($username);

            $mailbox = 'imap.gmail.com:993';
            $encryption = 'ssl';
            $enc = '';
            if ($encryption != null && isset($encryption) && $encryption == 'ssl')
                $enc = '/imap/ssl/novalidate-cert';
            else if ($encryption != null && isset($encryption) && $encryption == 'tls')
                $enc = '/imap/tls';
            $this->mailbox = '{' . $mailbox . $enc . '}';
            if (!$this->imap = imap_open($this->mailbox, $username, $password)) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * close connection
     */
    function __destruct() {
        if ($this->imap !== false)
            imap_close($this->imap);
    }

    /**
     * returns true after successfull connection
     *
     * @return bool true on success
     */
    public function isConnected() {
        return $this->imap !== false;
    }

    /**
     * returns last imap error
     *
     * @return string error message
     */
    public function getError() {
        return imap_last_error();
    }

    /**
     * select given folder
     *
     * @return bool successfull opened folder
     * @param $folder name
     */
    public function selectFolder($folder) {

        try {
            $folder = preg_replace('/%20+/', ' ', $folder);
            $folder = preg_replace('/~+/', '/', $folder);
            $result = imap_reopen($this->imap, $this->mailbox . $folder);
            if ($result === true) {
                $this->folder = $folder;
                return TRUE;
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Get the current folder
     * @return type
     */
    public function getFolder() {
        return (strlen($this->folder)) ? strtolower($this->folder) : 'inbox';
    }

    /**
     * returns all available folders
     *
     * @return array with foldernames
     */
    public function getFolders() {
        $folders = imap_list($this->imap, $this->mailbox, '*');
        /* echo "<pre>";
          print_r($folders);
          die; */
        return str_replace($this->mailbox, '', $folders);
    }

    /**
     * returns the number of messages in the current folder
     *
     * @return int message count
     */
    public function countMessages() {
        return imap_num_msg($this->imap);
    }

    /**
     * returns the number of unread messages in the current folder
     *
     * @return int message count
     */
    public function countUnreadMessages() {
        $result = imap_search($this->imap, 'UNSEEN');
        if ($result === false)
            return 0;
        return count($result);
    }

    /**
     * returns unseen emails in the current folder
     *
     * @return array messages
     * @param $withbody without body
     */
    public function getUnreadMessages($withbody = true) {
        $result = imap_search($this->imap, 'UNSEEN');
        foreach ($result as $k => $i) {
            $emails[] = $this->formatMessage($i, $withbody);
        }
        return $emails;
    }

    /**
     * returns all emails in the current folder
     *
     * @return array messages
     * @param $withbody without body
     */
    public function getMessages() {

        $count_all_msg = imap_num_msg($this->imap);
        $count_unread_msg = $this->countUnreadMessages();
        $numMessages = $count_all_msg;
        $message_per_page = $this->getMessagesPerPage();
        $temp = array();
        $message_per_page = $count_all_msg <= $message_per_page ? $count_all_msg : $message_per_page;

        $val = (($this->getCurrentPage() - 1) * $message_per_page);
        $numMessages -= $val;
        $start_index = $val + 1;
        $end_index = $val + $message_per_page;


        
        for ($i = $numMessages; $i > ($numMessages - $message_per_page); $i--) {
            $header = imap_header($this->imap, $i);

            $classReadUnread = ($header->Unseen == 'U') ? 'web_mail_unread' : 'web_mail_read';
            $classSttarred = ($header->Flagged == 'F') ? 'fa fa-star' : 'fa fa-star-o';

            $fromInfo = $header->from[0];
            $replyInfo = $header->reply_to[0];
            $to = $header->to[0];

            // get attachments
            $mailStruct = imap_fetchstructure($this->imap, $i);
            $attachments = $this->attachments2name($this->getAttachments($this->imap, $i, $mailStruct, ''));

            $subject = '';
            if (isset($header->subject) && strlen($header->subject) > 0) {
                $subject = imap_mime_header_decode($header->subject);
                $subject = $subject[0]->text;
            } else {
                $subject = '';
            }

            if (isset($fromInfo->personal)) {
                $from = $fromInfo->personal;
            } else {
                $from = (isset($fromInfo->mailbox) && isset($fromInfo->host)) ? $fromInfo->mailbox . '@' . $fromInfo->host : '';
            }

            $details = array(
                'from' => $from,
                'replyAddr' => (isset($replyInfo->mailbox) && isset($replyInfo->host)) ? $replyInfo->mailbox . '@' . $replyInfo->host : '',
                'replyName' => (isset($replyInfo->personal)) ? $replyInfo->personal : '',
                'subject' => $subject,
                'udate' => (isset($header->date)) ? $this->getDateFormat($header->date) : '',
                'header_date' => (isset($header->date)) ? date('Y-m-d H:i:s', strtotime($header->date)) : '',
                'classReadUnread' => $classReadUnread,
                'classSttarred' => $classSttarred,
                'attachments' => (count($attachments) > 0) ? count($attachments) : 0
            );
            $sent_folder = $this->getFolder();
            if ($sent_folder === 'sent') {
                $details['fromAddr'] = (isset($to->mailbox) && isset($to->host)) ? $to->mailbox . '@' . $to->host : '';
                $details['fromName'] = (isset($to->personal)) ? $to->personal : '';
            }


            $uid = imap_uid($this->imap, $i);
            $temp[$uid] ['details'] = $details;
            $temp[$uid]['folder'] = $this->folder;
            $temp[$uid]['uid'] = $uid;
        }
        return array('details' => $temp, 'start_index' => $start_index, 'end_index' => $end_index, 'count_all_msg' => $count_all_msg, 'count_unread_msg' => $count_unread_msg);
    }

    /**
     * Get the mail using mail id.
     * @param $id
     * @param bool $withbody
     * @return array
     */
    public function formatMessage($uid, $withbody = true) {

        $id = imap_msgno($this->imap, $uid);
        $header = imap_headerinfo($this->imap, $id);

        //get frmo and from addres
        $fromInfo = $header->from[0];
        $subject = '';
        // get email data
        if (isset($header->subject) && strlen($header->subject) > 0) {
            $subject_array = imap_mime_header_decode($header->subject);
            foreach ($subject_array as $text)
                $subject .= $text->text;
        }

        $date = date('F j,Y h:i a', strtotime($header->date));


        $subject = $this->convertToUtf8($subject);
        $email = array(
            'to' => isset($header->to) ? implode(',', $this->arrayToAddress($header->to)) : '',
            'from' => (isset($fromInfo->mailbox) && isset($fromInfo->host)) ? $fromInfo->mailbox . '@' . $fromInfo->host : '',
            'fromName' => (isset($fromInfo->personal)) ? $fromInfo->personal : '',
            'date' => $date,
            'subject' => $subject,
            'uid' => $uid,
            'unread' => strlen(trim($header->Unseen)) > 0,
            'answered' => strlen(trim($header->Answered)) > 0
        );
        $email['cc'] = '';
        if (isset($header->cc)) {
            $email['cc'] = implode(',', $this->arrayToAddress($header->cc));
        }

        if (preg_match("/sent/i", $this->getFolder()))
            if (isset($header->bcc)) {
                $email['bcc'] = implode(',', $this->arrayToAddress($header->bcc));
            }

        // get email body
        if ($withbody === true) {
            $body = $this->getBody($uid);
            $email['body'] = $body['body'];
            $email['html'] = $body['html'];
        }

        // get attachments
        $mailStruct = imap_fetchstructure($this->imap, $id);
        $attachments = $this->attachments2name($this->getAttachments($this->imap, $id, $mailStruct, ''));
        if (count($attachments) > 0)
            $email['attachments'] = $attachments;
        $email['count_unread_msg'] = $this->countUnreadMessages();

        return $email;
    }

    /**
     * delete given message
     *
     * @return bool success or not
     * @param $id of the message
     */
    public function deleteMessage($id) {
        return $this->deleteMessages(array($id));
    }

    /**
     * delete messages
     *
     * @return bool success or not
     * @param $ids array of ids
     */
    public function deleteMessages($ids) {
        $response = imap_mail_move($this->imap, implode(',', $ids), $this->getTrash(), CP_UID);
        imap_expunge($this->imap);
        return $response;
    }

    /**
     * This method will delete message permanently from the mail box
     * @param type $id
     * @return type
     */
    public function permanentDeleteMessages($id) {
        $response = imap_delete($this->imap, $id, FT_UID);
        imap_expunge($this->imap);
        return $response;
    }

    /**
     * move given message in new folder
     *
     * @return bool success or not
     * @param $id of the message
     * @param $target new folder
     */
    public function moveMessage($id, $target) {
        return $this->moveMessages(array($id), $target);
    }

    /**
     * move given message in new folder
     *
     * @return bool success or not
     * @param $ids array of message ids
     * @param $target new folder
     */
    public function moveMessages($ids, $target) {
        if (imap_mail_move($this->imap, implode(',', $ids), $target, CP_UID) === false)
            return imap_expunge($this->imap);
    }

    /**
     * mark message as read
     *
     * @return bool success or not
     * @param $id of the message
     * @param $seen true = message is read, false = message is unread
     */
    public function setUnseenMessage($id, $seen = true) {

        $header = $this->getMessageHeader($id);
        if ($header == false)
            return false;

        $flags = '';
        $flags .= (strlen(trim($header->Answered)) > 0 ? '\\Answered ' : '');
        $flags .= (strlen(trim($header->Flagged)) > 0 ? '\\Flagged ' : '');
        $flags .= (strlen(trim($header->Deleted)) > 0 ? '\\Deleted ' : '');
        $flags .= (strlen(trim($header->Draft)) > 0 ? '\\Draft ' : '');

        $flags .= (($seen == true) ? '\\Seen ' : ' ');
        //echo '\n<br />'.$id.': '.$flags;
        imap_clearflag_full($this->imap, $id, '\\Seen', ST_UID);
        return imap_setflag_full($this->imap, $id, trim($flags), ST_UID);
    }

    public function setFlagMessage($id, $flag = TRUE) {

        imap_clearflag_full($this->imap, $id, '\\Flagged', ST_UID);
        if ($flag)
            return imap_setflag_full($this->imap, $id, "\\Flagged", ST_UID);
        return TRUE;
    }

    /**
     * return content of messages attachment
     *
     * @return binary attachment
     * @param $id of the message
     * @param $index of the attachment (default: first attachment)
     */
    public function getAttachment($id, $index = 0) {
        $user_id = $this->ci->login_user_id;
        // find message
        $attachments = false;
        $messageIndex = imap_msgno($this->imap, $id);
        $header = imap_headerinfo($this->imap, $messageIndex);
        $mailStruct = imap_fetchstructure($this->imap, $messageIndex);
        $attachments = $this->getAttachments($this->imap, $messageIndex, $mailStruct, '');

        if ($attachments == false)
            return false;

        // find attachment
        if ($index > count($attachments))
            return false;
        $attachment = $attachments[$index];

        // get attachment body
        $partStruct = imap_bodystruct($this->imap, imap_msgno($this->imap, $id), $attachment['partNum']);

        if (isset($partStruct->parameters) && is_array($partStruct->parameters))
            $filename = $partStruct->parameters[0]->value;
        elseif (isset($partStruct->dparameters) && is_array($partStruct->parameters))
            $filename = $partStruct->dparameters[0]->value;
        $message = imap_fetchbody($this->imap, $id, $attachment['partNum'], FT_UID);

        switch ($attachment['enc']) {
            case 0:
            case 1:
                $message = imap_8bit($message);
                break;
            case 2:
                $message = imap_binary($message);
                break;
            case 3:
                $message = imap_base64($message);
                break;
            case 4:
                $message = quoted_printable_decode($message);
                break;
        }
        /**
         *  Save file on temp dir on server.
         *  The files get automaticaly deleted.
         */
        $ini_val = ini_get('upload_tmp_dir');
        $upload_tmp_dir = $ini_val ? $ini_val : sys_get_temp_dir();
        $dir = $upload_tmp_dir . "/user_{$user_id}";
        if (is_dir($upload_tmp_dir) === false) {
            mkdir($dir, 0777);
        }
        $path = $dir . '/' . $attachment['name'];
        $fp = fopen($path, 'w+');
        fwrite($fp, $message);
        fclose($fp);

        return array(
            'path' => $path);
    }

    /**
     * add new folder
     *
     * @return bool success or not
     * @param $name of the folder
     */
    public function addFolder($name) {
        return imap_createmailbox($this->imap, $this->mailbox . $name);
    }

    /**
     * remove folder
     *
     * @return bool success or not
     * @param $name of the folder
     */
    public function removeFolder($name) {
        return imap_deletemailbox($this->imap, $this->mailbox . $name);
    }

    /**
     * rename folder
     *
     * @return bool success or not
     * @param $name of the folder
     * @param $newname of the folder
     */
    public function renameFolder($name, $newname) {
        return imap_renamemailbox($this->imap, $this->mailbox . $name, $this->mailbox . $newname);
    }

    /**
     * clean folder content of selected folder
     *
     * @return bool success or not
     */
    public function purge() {
        // delete trash and spam
        if ($this->folder == $this->getTrash() || strtolower($this->folder) == 'spam') {
            if (imap_delete($this->imap, '1:*') === false) {
                return false;
            }
            return imap_expunge($this->imap);

            // move others to trash
        } else {
            if (imap_mail_move($this->imap, '1:*', $this->getTrash()) == false)
                return false;
            return imap_expunge($this->imap);
        }
    }

    /**
     * returns all email addresses
     *
     * @return array with all email addresses or false on error
     */
    public function getAllEmailAddresses() {
        $saveCurrentFolder = $this->folder;
        $emails = array();
        foreach ($this->getFolders() as $folder) {
            $this->selectFolder($folder);
            foreach ($this->getMessages(false) as $message) {
                $emails[] = $message['from'];
                $emails = array_merge($emails, $message['to']);
                if (isset($message['cc']))
                    $emails = array_merge($emails, $message['cc']);
            }
        }
        $this->selectFolder($saveCurrentFolder);
        return array_unique($emails);
    }

    /**
     * save email in sent
     *
     * @return void
     * @param $header
     * @param $body
     */
    public function saveMessageInSent($header, $body) {
        return imap_append($this->imap, $this->mailbox . $this->getSent(), $header . '\r\n' . $body . '\r\n', '\\Seen');
    }

    // private helpers

    /**
     * get trash folder name or create new trash folder
     *
     * @return trash folder name
     */
    private function getTrash() {

        foreach ($this->getFolders() as $folder) {
            if (strtolower($folder) === 'trash' || strtolower($folder) === 'papierkorb') {
                return $folder;
            }
            if (strtolower($folder) === strtolower('[Gmail]/Trash')) {
                return $folder;
            }
        }

        // no trash folder found? create one
        //$this->addFolder('Trash');

        return 'Trash';
    }

    /**
     * get sent folder name or create new sent folder
     *
     * @return sent folder name
     */
    private function getSent() {
        foreach ($this->getFolders() as $folder) {
            if (strtolower($folder) === 'sent' || strtolower($folder) === 'gesendet')
                return $folder;
            if (strtolower($folder) === strtolower('[Gmail]/Sent')) {
                return $folder;
            }
        }

        // no sent folder found? create one
        //$this->addFolder('Sent');

        return 'Sent';
    }

    /**
     * fetch message by id
     *
     * @return header
     * @param $id of the message
     */
    private function getMessageHeader($id) {
        $count = $this->countMessages();
        for ($i = 1; $i <= $count; $i++) {
            $uid = imap_uid($this->imap, $i);
            if ($uid == $id) {
                $header = imap_headerinfo($this->imap, $i);
                return $header;
            }
        }
        return false;
    }

    /**
     * convert attachment in array(name => ..., size => ...).
     *
     * @return array
     * @param $attachments with name and size
     */
    private function attachments2name($attachments) {
        $names = array();
        foreach ($attachments as $attachment) {
            $names[] = array(
                'name' => $attachment['name'],
                'size' => $attachment['size']
            );
        }
        return $names;
    }

    /**
     * convert imap given address in string
     *
     * @return string in format 'Name <email@bla.de>'
     * @param $headerinfos the infos given by imap
     */
    private function toAddress($headerinfos) {
        $email = '';
        $name = '';
        if (isset($headerinfos->mailbox) && isset($headerinfos->host)) {
            $email = $headerinfos->mailbox . '@' . $headerinfos->host;
        }

        if (!empty($headerinfos->personal)) {
            $name = imap_mime_header_decode($headerinfos->personal);
            $name = $name[0]->text;
        } else {
            $name = $email;
        }
        $name = $this->convertToUtf8($name);
        return $email;
    }

    /**
     * converts imap given array of addresses in strings
     *
     * @return array with strings (e.g. ['Name <email@bla.de>', 'Name2 <email2@bla.de>']
     * @param $addresses imap given addresses as array
     */
    private function arrayToAddress($addresses) {
        $addressesAsString = array();
        foreach ($addresses as $address) {
            $addressesAsString[] = $this->toAddress($address);
        }
        return $addressesAsString;
    }

    /**
     * returns body of the email. First search for html version of the email, then the plain part.
     *
     * @return string email body
     * @param $uid message id
     */
    private function getBody($uid) {
        $body = $this->get_part($this->imap, $uid, 'TEXT/HTML');
        $html = true;
        // if HTML body is empty, try getting text body
        if ($body == '') {
            $body = $this->get_part($this->imap, $uid, 'TEXT/PLAIN');
            $html = false;
        }
        $body = $this->convertToUtf8($body);
        return array('body' => $body, 'html' => $html);
    }

    /**
     * convert to utf8 if necessary.
     *
     * @return true or false
     * @param $string utf8 encoded string
     */
    function convertToUtf8($str) {
        if (mb_detect_encoding($str, 'UTF-8, ISO-8859-1, GBK') != 'UTF-8')
            $str = utf8_encode($str);
        $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
        return $str;
    }

    /**
     * returns a part with a given mimetype
     * taken from http://www.sitepoint.com/exploring-phps-imap-library-2/
     *
     * @return string email body
     * @param $imap imap stream
     * @param $uid message id
     * @param $mimetype
     */
    private function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
        if (!$structure) {
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
        }
        if ($structure) {
            if ($mimetype == $this->get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID | FT_PEEK);
                switch ($structure->encoding) {
                    case 3: return imap_base64($text);
                    case 4: return imap_qprint($text);
                    default: return $text;
                }
            }

            // multipart 
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = '';
                    if ($partNumber) {
                        $prefix = $partNumber . '.';
                    }
                    $data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    /**
     * extract mimetype
     * taken from http://www.sitepoint.com/exploring-phps-imap-library-2/
     *
     * @return string mimetype
     * @param $structure
     */
    private function get_mime_type($structure) {
        $primaryMimetype = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');

        if ($structure->subtype) {
            return $primaryMimetype[(int) $structure->type] . '/' . $structure->subtype;
        }
        return 'TEXT/PLAIN';
    }

    /**
     * get attachments of given email
     * taken from http://www.sitepoint.com/exploring-phps-imap-library-2/
     *
     * @return array of attachments
     * @param $imap stream
     * @param $mailNum email
     * @param $part
     * @param $partNum
     */
    private function getAttachments($imap, $mailNum, $part, $partNum) {
        $attachments = array();

        if (isset($part->parts)) {
            foreach ($part->parts as $key => $subpart) {
                if ($partNum != "") {
                    $newPartNum = $partNum . "." . ($key + 1);
                } else {
                    $newPartNum = ($key + 1);
                }
                $result = $this->getAttachments($imap, $mailNum, $subpart, $newPartNum);
                if (count($result) != 0) {
                    array_push($attachments, $result);
                }
            }
        } else if (isset($part->disposition)) {

            if (strtolower($part->disposition) == "attachment") {
                if (isset($part->parameters) && is_array($part->parameters)) {

                    $partStruct = imap_bodystruct($imap, $mailNum, $partNum);
                    $attachmentDetails = array(
                        "name" => $part->parameters[0]->value,
                        "partNum" => $partNum,
                        "enc" => $partStruct->encoding,
                        "size" => $part->bytes
                    );
                    return $attachmentDetails;
                } elseif (isset($part->dparameters) && is_array($part->dparameters)) {

                    $partStruct = imap_bodystruct($imap, $mailNum, $partNum);
                    $attachmentDetails = array(
                        "name" => $part->dparameters[0]->value,
                        "partNum" => $partNum,
                        "enc" => $partStruct->encoding,
                        "size" => $part->bytes
                    );
                    return $attachmentDetails;
                }
            }
        }
        return $attachments;
    }

    /**
     * set messages per page
     */
    public function setMessagesPerPage($count) {
        $this->message_per_page = $count > 0 ? $count : $this->message_per_page;
    }

    /**
     * get the count of messages per page
     * @return type
     */
    private function getMessagesPerPage() {
        return $this->message_per_page;
    }

    /**
     * set the current page
     * @param type $page
     */
    public function setCurrentPage($page) {
        $this->current_page = $page > 0 ? $page : $this->current_page;
    }

    /**
     * Get the current page number
     * @return type
     */
    public function getCurrentPage() {
        return $this->current_page;
    }

    /**
     * get the date format for each email. If on same date then fetch the time only like gmail
     * @param type $date
     * @return type
     */
    public function getDateFormat($date) {

        $mail_date = date('jMY', strtotime($date));
        $today = date('jMY');
        // Compare both date
        if ($mail_date == $today) {
            $format_str = date('g:i a', strtotime($date));
        } else {
            $format_str = date('M j', strtotime($date));
        }
        return $format_str;
    }

    /**
     * Taking decision on showing next link or not
     * @param type $countMessages
     * @return type
     */
    public function get_next_page($countMessages) {
        $current_page = $this->current_page;
        $message_per_page = $this->message_per_page;

        $flag = false;
        $total_pages = ceil($countMessages / $message_per_page);
        // get total pages
        if ($current_page < $total_pages) {
            $flag = $current_page + 1;
        }
        return $flag;
    }

    /**
     * Taking decision on showing prev link or not
     * @return type
     */
    public function get_prev_page() {
        $current_page = $this->current_page;
        $flag = false;

        if ($current_page > 1) {
            $flag = $current_page - 1;
        }
        return $flag;
    }

    /**
     * This method save the composed mail in sent folder
     * @param type $to
     * @param type $subject
     * @param type $body
     * @param type $headers
     * @param type $cc
     * @param type $bcc
     * @param type $return_path
     * @return boolean
     */
    public function save_send_mail($to, $subject, $body, $headers, $cc, $bcc, $return_path) {
        try {

            //$username = $this->ci->reno_session->get_session('webmail_email');
            //$password = $this->ci->reno_session->get_session('webmail_pass');
            if (!$this->connectToMailBox($username, $password))
                return FALSE;

            // save the sent email to your Sent folder by just passing a string composed 
            // of the entire message + headers.  See imap_append() function for more details.
            // Notice the 'r' format for the date function, which formats the date correctly for messaging.
            $mail_box = $this->mailbox;
            imap_append($this->imap, '{$mail_box}INBOX.Sent', 'From: {$username}\r\n' .
                    'To: ' . $to . '\r\n' .
                    'Subject: ' . $subject . '\r\n' .
                    'Date: ' . date('r', strtotime('now')) . '\r\n' .
                    '\r\n' .
                    $body .
                    '\r\n'
            );
            return true;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Send the mail using imap providers
     * @param type $to
     * @param type $subject
     * @param type $body
     * @param type $headers
     * @param type $cc
     * @param type $bcc
     * @param type $return_path
     * @return boolean
     */
    public function sendMailUsingImap($to, $subject, $body, $headers, $cc, $bcc, $return_path) {


        //$username = $this->ci->reno_session->get_session('webmail_email');
        //$password = $this->ci->reno_session->get_session('webmail_pass');

        // get imap providers
        //$imap_details = $this->ci->reno_emailconstant->imap_providers_details($username);
        if (isset($imap_details['error']))
            if ($imap_details['error'])
                return FALSE;
        $mailbox = $imap_details['host'];
        $encryption = $imap_details['enc'];
        $enc = '';
        if ($encryption != null && isset($encryption) && $encryption == 'ssl')
            $enc = '/imap/ssl';
        else if ($encryption != null && isset($encryption) && $encryption == 'tls')
            $enc = '/imap/tls';
        $this->mailbox = "{" . $mailbox . $enc . "}";
        if ($this->imap = @imap_open($this->mailbox, $username, base64_decode($password))) {

            if (imap_mail($to, $subject, $body, $headers)) {
                // save the sent email to your Sent folder by just passing a string composed 
                // of the entire message  headers.  See imap_append() function for more details.
                // Notice the 'r' format for the date function, which formats the date correctly for messaging.
                $mail_box = $this->mailbox;
                imap_append($this->imap, "{$mail_box}INBOX.Sent", "From: {$username}\r\n" .
                        "To: " . $to . "\r\n" .
                        "Subject: " . $subject . "\r\n" .
                        "Date: " . date("r", strtotime("now")) . "\r\n" .
                        "\r\n" .
                        $body .
                        "\r\n"
                );
                return true;
            } else {
                return FALSE;
            }
        }
        return false;
    }

    /**
     * After sending mail we forcefully saving in sending folder
     * @param type $to
     * @param type $cc
     * @param type $bcc
     * @param type $path_attachment
     * @param type $attachments
     * @param type $subject
     * @param type $body
     * @return type
     */
    public function save_mail_in_sent($to, $cc, $bcc, $path_attachment, $attachments, $subject, $body, $destination_folder = 'Sent') {

        //$username = $this->ci->reno_session->get_session('webmail_email');
        $mail_box = $this->mailbox;

        $dmy = date('r', strtotime('now'));
        $stream = $this->imap;
        $boundary = "------=" . md5(uniqid(rand()));
        $header = "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";
        $header .="\r\n\r\n";
        $header .= "--$boundary\r\n";
        $header .= "Content-Type: text/html;\r\n\tcharset=\"ISO-8859-1\"\r\n";
        $header .= "Content-Transfer-Encoding: 8bit \r\n";
        $header .= "\r\n\r\n";
        $header .= "$body\r\n";
        $header .= "\r\n\r\n";
        $msg2 = '';
        if (count($attachments)) {
            foreach ($attachments as $msg_type => $attachment) {

                // For compose mail
                if ($msg_type == 'compose_msg') {
                    foreach ($attachment as $file_name) {
                        $file = $path_attachment . '/' . $file_name;
                        $msg2 .= $this->get_body_attachment($file, $file_name, $boundary);
                    }
                } elseif ($msg_type == 'forward_msg') {
                    foreach ($attachment as $file_name_with_path) {
                        $file = $file_name_with_path;
                        $file_name = basename($file_name_with_path);
                        $msg2 .= $this->get_body_attachment($file, $file_name, $boundary);
                    }
                }
            }
        }
        $msg3 = "--$boundary--\r\n";
        return imap_append($this->imap, "{$mail_box}{$destination_folder}", "From: {$username}\r\n" . "To: {$to}\r\n" . "Date: {$dmy}\r\n" . "Subject: {$subject}\r\n" . "{$header}\r\n" . "{$msg2}\r\n" . "{$msg3}\r\n");
    }

    /**
     * Return the body to use as attachment
     * @param type $file
     * @param type $file_name
     * @param type $boundary
     * @return string
     */
    public function get_body_attachment($file, $file_name, $boundary) {

        $fp = fopen("$file", "rb");
        $file_data = fread($fp, filesize("$file"));
        fclose($fp);
        $new_attachment = chunk_split(base64_encode($file_data));
        $msg2 = "--$boundary\r\n";
        $msg2 .= "Content-Transfer-Encoding: base64\r\n";
        $msg2 .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        $msg2 .= "\r\n";
        $msg2 .= $new_attachment . "\r\n";
        $msg2 .= "\r\n\r\n";
        return $msg2;
    }

    /**
     * Get latest mail
     * @param type $count_latest_msg
     * @return array
     */
    public function get_latest_msg($count_latest_msg = 1) {

        $numMessages = imap_num_msg($this->imap);
        $temp = array();

        $i = $numMessages; {
            $header = imap_header($this->imap, $i);

            $fromInfo = $header->from[0];
            $subject = '';
            if (isset($header->subject) && strlen($header->subject) > 0) {
                $subject = imap_mime_header_decode($header->subject);
                $subject = $subject[0]->text;
            }
            // get attachments
            $mailStruct = imap_fetchstructure($this->imap, $i);
            $attachments = $this->attachments2name($this->getAttachments($this->imap, $i, $mailStruct, ''));
            $uid = imap_uid($this->imap, $i);
            $from = '';
            if (isset($fromInfo->personal)) {
                $from = $fromInfo->personal;
            } elseif (isset($fromInfo->mailbox) && isset($fromInfo->host)) {
                $from = $fromInfo->mailbox . '@' . $fromInfo->host;
            }
            $details = array(
                'from' => $from,
                'subject' => substr($subject, 0, 45),
                'udate' => (isset($header->date)) ? $this->getDateFormat($header->date) : '',
                'header_date' => (isset($header->date)) ? date('Y-m-d H:i:s', strtotime($header->date)) : '',
                'uid' => $uid,
                'attachments' => (count($attachments) > 0) ? count($attachments) : 0
            );
            array_push($temp, $details);
        }
        return $temp;
    }

    /**
     * Get the latest unread mails
     * @param type $count_unread_msg
     * @return array
     */
    public function get_latest_unread_msg($count_unread_msg = 2) {

        $count_all_msg = imap_num_msg($this->imap);
        $numMessages = $count_all_msg;
        $temp = array();

        for ($i = $numMessages; $i > 0; $i--) {
            $header = imap_header($this->imap, $i);
            if (strtoupper($header->Unseen) == 'U') { // Message is unread
                $fromInfo = $header->from[0];
                $subject = '';
                if (isset($header->subject) && strlen($header->subject) > 0) {
                    $subject = imap_mime_header_decode($header->subject);
                    $subject = $subject[0]->text;
                }
                // get attachments
                $mailStruct = imap_fetchstructure($this->imap, $i);
                $attachments = $this->attachments2name($this->getAttachments($this->imap, $i, $mailStruct, ''));
                $uid = imap_uid($this->imap, $i);
                $from = '';
                if (isset($fromInfo->personal)) {
                    $from = $fromInfo->personal;
                } elseif (isset($fromInfo->mailbox) && isset($fromInfo->host)) {
                    $from = $fromInfo->mailbox . '@' . $fromInfo->host;
                }
                $details = array(
                    'from' => $from,
                    'subject' => substr($subject, 0, 45),
                    'udate' => (isset($header->date)) ? $this->getDateFormat($header->date) : '',
                    'header_date' => (isset($header->date)) ? date('Y-m-d H:i:s', strtotime($header->date)) : '',
                    'uid' => $uid,
                    'attachments' => (count($attachments) > 0) ? count($attachments) : 0
                );
                array_push($temp, $details);
                $count_unread_msg--;
                if (!$count_unread_msg) {
                    break;
                }
            }
        }
        return $temp;
    }

}
