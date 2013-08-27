<?php
require_once($CFG->dirroot . '/mod/elluminate/include/global-includes.php');

global $ELLUMINATE_CONTAINER;
$ELLUMINATE_CONTAINER = new Elluminate_Pimple();

//************** DATA ACCESS LAYER *****************/
$ELLUMINATE_CONTAINER['sessionDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Session_DAO();
});

$ELLUMINATE_CONTAINER['moodleDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_DAO();
});

$ELLUMINATE_CONTAINER['recordingDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Recordings_DAO();
});

$ELLUMINATE_CONTAINER['gradesDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Grades_DAO();
});

$ELLUMINATE_CONTAINER['groupDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Group_DAO();
});

$ELLUMINATE_CONTAINER['licenseDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_License_DAO();
});

$ELLUMINATE_CONTAINER['cacheDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Cache_DAO();
});

$ELLUMINATE_CONTAINER['preloadDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Preloads_DAO();
});

//************** MOODLE APIS *****************/
//Calendar
$ELLUMINATE_CONTAINER['calendarAPI'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_Calendar();
});

//Grades
$ELLUMINATE_CONTAINER['gradesAPI'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_GradeHelper();
});

$ELLUMINATE_CONTAINER['groupsAPI'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_GroupAccess();
});

$ELLUMINATE_CONTAINER['moduleConfig'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Config_Settings();
});

$ELLUMINATE_CONTAINER['auditDAO'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Audit_DAO();
});

//************** SAS SCHEDULING MANAGER ************/
$ELLUMINATE_CONTAINER['schedulingManager'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return Elluminate_WS_SchedulingManagerFactory::getSchedulingManager();
});

//************** CACHE MANAGEMENT  ************/
$ELLUMINATE_CONTAINER['cacheContentTypes'] = function ($c) {
   return array('RecordingContent', 'TelephonyContent', 'GuestLinkContent');
};

$ELLUMINATE_CONTAINER['cacheManager'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $cache = new Elluminate_Cache_Manager();
   $cache->cacheDAO = $c['cacheDAO'];
   $cache->schedulingManager = $c['schedulingManager'];
   $cache->cacheContentTypes = $c['cacheContentTypes'];
   $cache->initContentHandlers();
   return $cache;
});

$ELLUMINATE_CONTAINER['RecordingContent'] = function ($c) {
   $recordingContent = new Elluminate_Cache_URL_RecordingContent();
   $recordingContent->setSchedulingManager($c['schedulingManager']);
   return $recordingContent;
};

$ELLUMINATE_CONTAINER['TelephonyContent'] = function ($c) {
   $telephonyContent = new Elluminate_Cache_Telephony_Content();
   $telephonyContent->setSchedulingManager($c['schedulingManager']);
   return $telephonyContent;
};

$ELLUMINATE_CONTAINER['GuestLinkContent'] = function ($c) {
   $guestLinkContent = new Elluminate_Cache_URL_GuestLinkContent();
   $guestLinkContent->setSchedulingManager($c['schedulingManager']);
   return $guestLinkContent;
};

//************** LICENSES  ************/
$ELLUMINATE_CONTAINER['licenseManager'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $licenses = new Elluminate_License_Manager();
   $licenses->licenseDAO = $c['licenseDAO'];
   return $licenses;
});

//************** TELEPHONY  ************/
$ELLUMINATE_CONTAINER['telephonyManager'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $telephony = new Elluminate_Telephony_Manager();
   $telephony->licenseManager = $c['licenseManager'];
   $telephony->cacheManager = $c['cacheManager'];
   $telephony->schedulingManager = $c['schedulingManager'];
   return $telephony;
});

//************** GROUPS ****************//

$ELLUMINATE_CONTAINER['groupCustomNaming'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $customNaming = new Elluminate_Group_CustomNaming();
   $customNaming->validator = $c['sessionValidator'];
   return $customNaming;
});

$ELLUMINATE_CONTAINER['groupSessionInitializer'] = function ($c) {
   $initializer = new Elluminate_Group_SessionInitializer();
   $initializer->sessionConfiguration = $c['sessionConfiguration'];
   $initializer->sessionLoader = $c['sessionLoader'];

   return $initializer;
};

$ELLUMINATE_CONTAINER['groupSwitcher'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $switcher = new Elluminate_Group_Switcher();
   $switcher->moodleDAO = $c['moodleDAO'];
   $switcher->sessionLoader = $c['sessionLoader'];
   return $switcher;
});

//************** GRADING ****************//
$ELLUMINATE_CONTAINER['gradesFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Grades_Factory();
});

$ELLUMINATE_CONTAINER['gradesReport'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $gradeReports = new Elluminate_Grades_Reports();
   $gradeReports->gradesAPI = $c['gradesAPI'];
   $gradeReports->gradesFactory = $c['gradesFactory'];
   return $gradeReports;
});

$ELLUMINATE_CONTAINER['gradesAttendance'] = function ($c) {
   $attendance = new Elluminate_Grades_Attendance();
   $attendance->gradesAPI = $c['gradesAPI'];
   $attendance->gradesDAO = $c['gradesDAO'];
   $attendance->gradesFactory = $c['gradesFactory'];
   return $attendance;
};

$ELLUMINATE_CONTAINER['gradesGradeBook'] = function ($c) {
   $gradeBook = new Elluminate_Grades_Gradebook();
   $gradeBook->gradesAPI = $c['gradesAPI'];
   $gradeBook->gradesFactory = $c['gradesFactory'];
   return $gradeBook;
};

$ELLUMINATE_CONTAINER['gradesGradeBookEntry'] = function ($c) {
   return new Elluminate_Grades_GradebookEntry();
};

$ELLUMINATE_CONTAINER['sessionGrading'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $grading = new Elluminate_Session_Grading();
   $grading->gradesFactory = $c['gradesFactory'];
   return $grading;
});

//************** SESSION **************//
$ELLUMINATE_CONTAINER['sessionFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Session_Factory();
});

$ELLUMINATE_CONTAINER['sessionValidator'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $sessionValidator = new Elluminate_Session_Validator();
   $sessionValidator->moodleOutput = $c['htmlOutput'];

   return $sessionValidator;
});

//Session DB/API Loader
$ELLUMINATE_CONTAINER['sessionLoader'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $loader = new Elluminate_Session_Loader();
   $loader->sessionDAO = $c['sessionDAO'];
   $loader->sessionFactory = $c['sessionFactory'];
   return $loader;
});

//Session User Management
$ELLUMINATE_CONTAINER['sessionUsers'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Session_Users();
});
//************** RECORDINGS **************//
$ELLUMINATE_CONTAINER['recordingFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Recordings_Factory();
});

$ELLUMINATE_CONTAINER['recordingLoader'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $loader = new Elluminate_Recordings_Loader();
   $loader->recordingDAO = $c['recordingDAO'];
   $loader->recordingFactory = $c['recordingFactory'];
   return $loader;
});

$ELLUMINATE_CONTAINER['recordingStatusUpdater'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $statusUpdater = new Elluminate_Recordings_StatusUpdate();
   $statusUpdater->recordingDAO = $c['recordingDAO'];
   $statusUpdater->recordingLoader = $c['recordingLoader'];
   $statusUpdater->schedulingManager = $c['schedulingManager'];
   $statusUpdater->cacheManager = $c['cacheManager'];
   return $statusUpdater;
});

//************** PRELOADS **************//
$ELLUMINATE_CONTAINER['preload'] = function ($c) {
   $preload = new Elluminate_Preload();
   $preload->preloadDAO = $c['preloadDAO'];
   $preload->serverSchedulingManager = $c['schedulingManager'];
   return $preload;
};

$ELLUMINATE_CONTAINER['preloadFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Preloads_Factory();
});

//************* HTML VIEW ************//
$ELLUMINATE_CONTAINER['htmlTable'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_Table();
});

$ELLUMINATE_CONTAINER['htmlOutput'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_Output();
});

$ELLUMINATE_CONTAINER['sessionView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $sessionView = new Elluminate_HTML_Session_View();
   $sessionView->sessionTable = $c['htmlTable'];
   $sessionView->groupSwitchTable = $c['htmlTable'];
   $sessionView->output = $c['htmlOutput'];
   $sessionView->cacheManager = $c['cacheManager'];
   $sessionView->licenseManager = $c['licenseManager'];
   $sessionView->groupAPI = $c['groupsAPI'];
   return $sessionView;
});

$ELLUMINATE_CONTAINER['recordingTable'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingTable = new Elluminate_HTML_Recording_Table();
   $recordingTable->htmlTable = $c['htmlTable'];
   $recordingTable->recordingLoader = $c['recordingLoader'];
   $recordingTable->output = $c['htmlOutput'];
   $recordingTable->permissions = $c['recordingPermissions'];
   return $recordingTable;
});

$ELLUMINATE_CONTAINER['recordingListView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingList = new Elluminate_HTML_Recording_ListView();
   $recordingList->output = $c['htmlOutput'];
   $recordingList->recordingListTable = $c['recordingTable'];
   $recordingList->recordingLoader = $c['recordingLoader'];
   return $recordingList;
});

$ELLUMINATE_CONTAINER['telephonySettingsView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $telephonySettings = new Elluminate_HTML_TelephonySettings();
   $telephonySettings->licenseManager = $c['licenseManager'];
   return $telephonySettings;
});

$ELLUMINATE_CONTAINER['userEditorView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $userEditor = new Elluminate_HTML_UserEditor();
   $userEditor->sessionUserHelper = $c['sessionUsers'];
   $userEditor->sessionCapabilities = $c['sessionCapabilities'];
   return $userEditor;
});

$ELLUMINATE_CONTAINER['convertView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $convertView = new Elluminate_HTML_Recording_ConvertView();
   $convertView->output = $c['htmlOutput'];
   $convertView->recordingTable = $c['recordingTable'];
   return $convertView;
});

$ELLUMINATE_CONTAINER['attendView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $attendView = new Elluminate_HTML_AttendView();
   $attendView->gradesAPI = $c['gradesAPI'];
   $attendView->gradesFactory = $c['gradesFactory'];
   $attendView->sessionUsers = $c['sessionUsers'];
   return $attendView;
});

$ELLUMINATE_CONTAINER['recordingPlayView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingPlayView = new Elluminate_HTML_Recording_PlayView();
   $recordingPlayView->output = $c['htmlOutput'];
   $recordingPlayView->cacheManager = $c['cacheManager'];
   $recordingPlayView->recordingStatusUpdater = $c['recordingStatusUpdater'];
   return $recordingPlayView;
});

$ELLUMINATE_CONTAINER['recordingDetailView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingDetailView = new Elluminate_HTML_Recording_DetailView();
   $recordingDetailView->recordingTable = $c['htmlTable'];
   $recordingDetailView->output = $c['htmlOutput'];
   $recordingDetailView->statusUpdater = $c['recordingStatusUpdater'];
   return $recordingDetailView;
});

$ELLUMINATE_CONTAINER['auditReport'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $auditReport = new Elluminate_Audit_Report();
   $auditReport->output = $c['htmlOutput'];
   $auditReport->auditDAO = $c['auditDAO'];
   return $auditReport;
});

$ELLUMINATE_CONTAINER['licenseView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $licenseView = new Elluminate_HTML_LicenseView();
   $licenseView->licenseManager = $c['licenseManager'];
   return $licenseView;
});

$ELLUMINATE_CONTAINER['preloadView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $preloadView = new Elluminate_HTML_PreloadView();
   $preloadView->preloadFactory = $c['preloadFactory'];
   return $preloadView;
});

$ELLUMINATE_CONTAINER['logView'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_HTML_LogView();
});

//************* PERMISSION MANAGEMENT ******//
//Wrapper for all calls to moodle capabilities API
$ELLUMINATE_CONTAINER['moodleCapabilities'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_Moodle_Capabilities();
});

$ELLUMINATE_CONTAINER['sessionCapabilities'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $sessionCapabilities = new Elluminate_Session_Capabilities();
   $sessionCapabilities->capabilitiesChecker = $c['moodleCapabilities'];
   return $sessionCapabilities;
});

$ELLUMINATE_CONTAINER['sessionPermissions'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $sessionPermissions = new Elluminate_Session_Permissions();
   $sessionPermissions->groupsAPI = $c['groupsAPI'];
   $sessionPermissions->capabilities = $c['sessionCapabilities'];
   return $sessionPermissions;
});

$ELLUMINATE_CONTAINER['recordingCapabilities'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingCapabilities = new Elluminate_Recordings_Capabilities();
   $recordingCapabilities->capabilitiesChecker = $c['moodleCapabilities'];
   return $recordingCapabilities;
});

$ELLUMINATE_CONTAINER['recordingPermissions'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $recordingPermissions = new Elluminate_Recordings_Permissions();
   $recordingPermissions->recordingCapabilities = $c['recordingCapabilities'];
   $recordingPermissions->licenseManager = $c['licenseManager'];
   return $recordingPermissions;
});

//************* SESSION CLASSES ************//

$ELLUMINATE_CONTAINER['session'] = function ($c) {
   $newSession = new Elluminate_Session();
   //Main Server Manager
   $newSession->sessionServerManager = $c['schedulingManager'];

   //Moodle APIs
   $newSession->sessionCalendar = $c['calendarAPI'];
   $newSession->sessionGrading = $c['sessionGrading'];

   //DAO
   $newSession->sessionDAO = $c['sessionDAO'];
   $newSession->moodleDAO = $c['moodleDAO'];
   $newSession->recordingDAO = $c['recordingDAO'];

   //Telephony
   $newSession->telephonyManager = $c['telephonyManager'];

   //Preloads
   $newSession->preloadFactory = $c['preloadFactory'];

   //Users
   $newSession->sessionUsers = $c['sessionUsers'];

   return $newSession;
};

$ELLUMINATE_CONTAINER['groupsession'] = function ($c) {
   $newSession = new Elluminate_Group_Session();
   //Main Server Manager
   $newSession->sessionServerManager = $c['schedulingManager'];

   //Moodle APIs
   $newSession->sessionCalendar = $c['calendarAPI'];
   $newSession->sessionGrading = $c['sessionGrading'];

   //DAO
   $newSession->sessionDAO = $c['sessionDAO'];
   $newSession->moodleDAO = $c['moodleDAO'];
   $newSession->recordingDAO = $c['recordingDAO'];

   //Telephony
   $newSession->telephonyManager = $c['telephonyManager'];

   //Preloads
   $newSession->preloadFactory = $c['preloadFactory'];

   //Groups
   $newSession->groupDAO = $c['groupDAO'];
   $newSession->groupAPI = $c['groupsAPI'];
   $newSession->customNamingHelper = $c['groupCustomNaming'];

   //Required for loading of child sessions
   $newSession->sessionLoader = $c['sessionLoader'];
   return $newSession;
};

$ELLUMINATE_CONTAINER['groupchildsession'] = function ($c) {
   $newSession = new Elluminate_Group_ChildSession();
   //Main Server Manager
   $newSession->sessionServerManager = $c['schedulingManager'];

   //Moodle APIs
   $newSession->sessionCalendar = $c['calendarAPI'];
   $newSession->sessionGrading = $c['sessionGrading'];

   //DAO
   $newSession->sessionDAO = $c['sessionDAO'];
   $newSession->moodleDAO = $c['moodleDAO'];
   $newSession->recordingDAO = $c['recordingDAO'];

   //Telephony
   $newSession->telephonyManager = $c['telephonyManager'];

   //Preloads
   $newSession->preloadFactory = $c['preloadFactory'];

   //Groups
   $newSession->groupDAO = $c['groupDAO'];
   $newSession->groupAPI = $c['groupsAPI'];
   $newSession->customNamingHelper = $c['groupCustomNaming'];
   return $newSession;
};

//************* SESSION CONTROLLERS ************//

$ELLUMINATE_CONTAINER['sessionConfiguration'] = function ($c) {
   $sessionConfig = new Elluminate_Session_Configuration();
   //Session Users
   $sessionConfig->sessionUsers = $c['sessionUsers'];

   return $sessionConfig;
};

$ELLUMINATE_CONTAINER['sessionAddController'] = function ($c) {
   $sessionAddController = new Elluminate_Session_Controller_Add();
   //Session Loader
   $sessionAddController->sessionLoader = $c['sessionLoader'];

   //Session Configuration Controller
   $sessionAddController->sessionConfiguration = $c['sessionConfiguration'];

   //Session Validator
   $sessionAddController->sessionValidator = $c['sessionValidator'];

      //Session Grading
   $sessionAddController->sessionGrading = $c['sessionGrading'];

   $sessionAddController->sessionCalendar = $c['calendarAPI'];

   $sessionAddController->telephonyManager = $c['telephonyManager'];

   return $sessionAddController;
};

$ELLUMINATE_CONTAINER['sessionUpdateController'] = function ($c) {
   $sessionUpdateController = new Elluminate_Session_Controller_Update();

   //Session Loader
   $sessionUpdateController->sessionLoader = $c['sessionLoader'];

   //Session Configuration Controller
   $sessionUpdateController->sessionConfiguration = $c['sessionConfiguration'];

   //Session Validator
   $sessionUpdateController->sessionValidator = $c['sessionValidator'];

   //Session Grading
   $sessionUpdateController->sessionGrading = $c['sessionGrading'];

   $sessionUpdateController->sessionCalendar = $c['calendarAPI'];

   $sessionUpdateController->telephonyManager = $c['telephonyManager'];

   return $sessionUpdateController;
};

//************* RECORDING CLASSES ************//
$ELLUMINATE_CONTAINER['recording'] = function ($c) {
   $recording = new Elluminate_Recording();
   $recording->serverRecordingManager = $c['schedulingManager'];
   $recording->recordingDAO = $c['recordingDAO'];
   return $recording;
};

$ELLUMINATE_CONTAINER['recordingFile'] = function ($c) {
   $recordingFile = new Elluminate_Recordings_File();
   $recordingFile->recordingDAO = $c['recordingDAO'];
   return $recordingFile;
};
//************* CRON ************//

//If no scheduler is set, no cron actions will be run.
$ELLUMINATE_CONTAINER['cronActionList'] = array();

$ELLUMINATE_CONTAINER['SAScronActionList'] = array('cronRecordingAddAction', 'cronRecordingUpdateAction', 'cronLicenseCheckAction');
$ELLUMINATE_CONTAINER['ELMcronActionList'] = array('ELMcronRecordingAddAction');

$ELLUMINATE_CONTAINER['cronRunner'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $cronRunner = new Elluminate_Cron_Runner();
   $cronRunner->moodleDAO = $c['moodleDAO'];
   $cronRunner->moduleConfig = $c['moduleConfig'];
   return $cronRunner;
});

$ELLUMINATE_CONTAINER['cronLicenseCheckAction'] = function ($c) {
   $licenseCheckAction = new Elluminate_Cron_LicenseCheckAction();
   $licenseCheckAction->schedulingManager = $c['schedulingManager'];
   $licenseCheckAction->moodleDAO = $c['moodleDAO'];
   $licenseCheckAction->licenseManager = $c['licenseManager'];
   return $licenseCheckAction;
};

$ELLUMINATE_CONTAINER['cronRecordingAddAction'] = function ($c) {
   $recordingAddAction = new Elluminate_Cron_RecordingAddAction();
   $recordingAddAction->schedulingManager = $c['schedulingManager'];
   $recordingAddAction->recordingDAO = $c['recordingDAO'];
   $recordingAddAction->sessionLoader = $c['sessionLoader'];
   $recordingAddAction->statusUpdater = $c['recordingStatusUpdater'];
   return $recordingAddAction;
};

$ELLUMINATE_CONTAINER['cronRecordingUpdateAction'] = function ($c) {
   $recordingUpdateAction = new Elluminate_Cron_RecordingUpdateAction();
   $recordingUpdateAction->statusUpdater = $c['recordingStatusUpdater'];
   $recordingUpdateAction->licenseManager = $c['licenseManager'];
   return $recordingUpdateAction;
};

$ELLUMINATE_CONTAINER['ELMcronRecordingAddAction'] = function ($c) {
   $recordingAddAction = new Elluminate_Cron_ElmRecordingAddAction();
   $recordingAddAction->schedulingManager = $c['schedulingManager'];
   $recordingAddAction->recordingDAO = $c['recordingDAO'];
   $recordingAddAction->sessionLoader = $c['sessionLoader'];
   $recordingAddAction->cacheManager = $c['cacheManager'];
   $recordingAddAction->recordingFactory = $c['recordingFactory'];
   return $recordingAddAction;
};

//************************** Web Service Helpers ******************//
$ELLUMINATE_CONTAINER['soapHelper'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_WS_SOAP_Helper();
});

$ELLUMINATE_CONTAINER['sasResponseFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_WS_SAS_Response_Factory();
});

$ELLUMINATE_CONTAINER['elmResponseFactory'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   return new Elluminate_WS_ELM_Response_Factory();
});

//************************** API IMPLEMENTATIONS ******************//

//Default WSDL:  This is required during the initial api check when we don't
//know if we're running ELM or SAS yet.  In this scenario, the SAS wsdl works
//fine because we're not doing any complex response processing.
$ELLUMINATE_CONTAINER['WSDL'] = '/mod/elluminate/web/ws/webservice-3.2.wsdl';

$ELLUMINATE_CONTAINER['SASImplementation'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $sasImplementation = new Elluminate_WS_SAS_Implementation();
   $sasImplementation->soapHelper = $c['soapHelper'];
   $sasImplementation->responseFactory = $c['sasResponseFactory'];
   return $sasImplementation;
});

$ELLUMINATE_CONTAINER['SASWSDL'] = '/mod/elluminate/web/ws/webservice-3.2.wsdl';

$ELLUMINATE_CONTAINER['ELMImplementation'] = $ELLUMINATE_CONTAINER->share(function ($c) {
   $elmImplementation = new Elluminate_WS_ELM_Implementation();
   $elmImplementation->soapHelper = $c['soapHelper'];
   $elmImplementation->responseFactory = $c['elmResponseFactory'];
   return $elmImplementation;
});

$ELLUMINATE_CONTAINER['ELMWSDL'] = '/mod/elluminate/web/ws/elm-3.0.wsdl';

//************************** SAS API RESPONSE HANDLERS ******************//
$ELLUMINATE_CONTAINER['RecordingResponse'] = function ($c) {
   $recordingResponse = new Elluminate_WS_SAS_Response_RecordingResponse();
   $recordingResponse->recordingFactory = $c['recordingFactory'];
   return $recordingResponse;
};

$ELLUMINATE_CONTAINER['RecordingFileResponse'] = function ($c) {
   $recordingResponse = new Elluminate_WS_SAS_Response_RecordingFileResponse();
   $recordingResponse->recordingFactory = $c['recordingFactory'];
   return $recordingResponse;
};

$ELLUMINATE_CONTAINER['url'] = function ($c) {
   return new Elluminate_WS_SAS_Response_Url();
};

$ELLUMINATE_CONTAINER['OptionLicenseResponse'] = function ($c) {
   return new Elluminate_WS_SAS_Response_OptionLicenseResponse();
};

$ELLUMINATE_CONTAINER['telephonyType'] = function ($c) {
   return new Elluminate_WS_SAS_Response_TelephonyType();
};

$ELLUMINATE_CONTAINER['SessionResponse'] = function ($c) {
   return new Elluminate_WS_SAS_Response_SessionResponse();
};

$ELLUMINATE_CONTAINER['PresentationResponse'] = function ($c) {
   return new Elluminate_WS_SAS_Response_PresentationResponse();
};

$ELLUMINATE_CONTAINER['sessionId'] = function ($c) {
   return new Elluminate_WS_SAS_Response_SessionId();
};

//************************** ELM API RESPONSE HANDLERS ******************//
$ELLUMINATE_CONTAINER['SetSessionResponse'] = function ($c) {
   return new Elluminate_WS_ELM_Response_SetSessionResponse();
};

$ELLUMINATE_CONTAINER['ListRecordingLongResponse'] = function ($c) {
   $recordingLongResponse = new Elluminate_WS_ELM_Response_ListRecordingLongResponse();
   $recordingLongResponse->recordingFactory = $c['recordingFactory'];
   return $recordingLongResponse;
};

$ELLUMINATE_CONTAINER['url'] = function ($c) {
   return new Elluminate_WS_ELM_Response_Url();
};

$ELLUMINATE_CONTAINER['emailBody'] = function ($c) {
   return new Elluminate_WS_ELM_Response_EmailBody();
};

$ELLUMINATE_CONTAINER['UploadRepositoryPresentationResponse'] = function ($c) {
   return new Elluminate_WS_ELM_Response_UploadRepositoryPresentationResponse();
};
