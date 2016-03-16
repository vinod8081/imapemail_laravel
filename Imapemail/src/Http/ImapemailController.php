<?php

namespace Codebank\Imapemail\Http;

use Codebank\Imapemail\Imapemail;
use Codebank\Imapemail\RenoImap;
use App\Http\Controllers\Controller;

class ImapemailController extends Controller {

    private $userEmail = 'xxxxxxxxxxxxx@gmail.com';
    private $userPass  = 'xxxxxx';
    public $imapObj    = null;

    public function __construct() {
        $this->imapObj = new RenoImap();
    }

    public function getUserImapemailList() {

        $imapemails = Imapemail::orderBy( 'created_at' )->get();
        return view( 'imapemail::imapemail-list' )->with( 'imapemails', $imapemails );
    }

    public function getEmailsList( $current_page = 1 ) {
        // get the user details

        $userEmail = $this->userEmail;
        $userPass  = $this->userPass;
        if( !$this->checkImapConnection( $userEmail, $userPass ) ) {
            return 'Error';
        }
        else {
            $folderView = $this->getFolders();
            $details    = $this->getEmails( $current_page );

            $details['folderView'] = $folderView;
            return view( 'imapemail::email-list', compact( 'details' ) );
        }
    }

    public function checkImapConnection( $userEmail, $userPass ) {

        $imapResponse = $this->imapObj->connectToMailBox( $userEmail, $userPass );
        //dd($imapResponse);
        return $imapResponse;
    }

    public function getEmails( $current_page = 1 ) {
        $this->imapObj->setMessagesPerPage( 10 );
        $this->imapObj->setCurrentPage( $current_page );
        $details = $this->imapObj->getMessages();
        return $details;
        //dd($details);
        //return view('imapemail::email-list', compact('details'));
    }

    public function getFolders() {
        $folders        = $this->imapObj->getFolders();
        $view           = View( 'imapemail::folder-list', ['folders' => $folders ] );
        //$folderViewHtml = view('imapemail::folder-list', compact('folders'))->render();
        $folderViewHtml = $view->render();
        return $folderViewHtml;
    }

}
