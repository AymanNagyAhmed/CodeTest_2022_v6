<?php

namespace App\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * Display a listing of bookings.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        if (!property_exists($request, '__authenticatedUser')) {
            return response(['error' => 'Authenticated user not found']);
        }

        try {
            if ($user_id = $request->get('user_id')) {
                $response = $this->repository->getUsersJobs($user_id);
            } elseif ($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
                $response = $this->repository->getAll($request);
            } else {
                return response(['error' => 'Unauthorized']);
            }

            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        try {
            $job = $this->repository->with('translatorJobRel.user')->find($id);

            if (!$job) {
                return response(['error' => 'Job not found']);
            }

            return response($job);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        if (!property_exists($request, '__authenticatedUser')) {
            return response(['error' => 'Authenticated user not found']);
        }

        $data = $request->all();

        try {
            $response = $this->repository->store($request->__authenticatedUser, $data);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        if (!property_exists($request, '__authenticatedUser')) {
            return response(['error' => 'Authenticated user not found']);
        }

        $data = $request->all();
        $cuser = $request->__authenticatedUser;

        try {
            $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if ($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);
    }

    /**
     * Handle the request for when the customer does not call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function customerNotCall(Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        $data = $request->all();

        try {
            $response = $this->repository->customerNotCall($data);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getPotentialJobs(Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        if (!property_exists($request, '__authenticatedUser')) {
            return response(['error' => 'Authenticated user not found']);
        }

        $user = $request->__authenticatedUser;

        try {
            $response = $this->repository->getPotentialJobs($user);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        } else {
            $distance = "";
        }
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } else {
            $time = "";
        }
        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } else {
            $session = "";
        }

        if ($data['flagged'] == 'true') {
            if ($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } else {
            $manually_handled = 'no';
        }

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } else {
            $by_admin = 'no';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } else {
            $admincomment = "";
        }
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response('Record updated!');
    }

    /**
     * Reopens a booking.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function reopen(Request $request)
    {
        if (!$request instanceof Request) {
            return response(['error' => 'Invalid request']);
        }

        $data = $request->all();

        try {
            $response = $this->repository->reopen($data);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Resends notifications for a specific job.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function resendNotifications(Request $request)
    {
        $data = $request->all();

        if (!isset($data['jobid'])) {
            return response(['error' => 'Job ID is required']);
        }

        $job = $this->repository->find($data['jobid']);

        if (!$job) {
            return response(['error' => 'Job not found']);
        }

        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendNotificationTranslator($job, $job_data, '*');
            return response(['success' => 'Push sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();

        if (!isset($data['jobid'])) {
            return response(['error' => 'Job ID is required']);
        }

        $job = $this->repository->find($data['jobid']);

        if (!$job) {
            return response(['error' => 'Job not found']);
        }

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }
}
