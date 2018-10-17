<?php
/**
 * Created by PhpStorm.
 * User: gengruifeng
 * Date: 2018/9/20
 * Time: 上午9:42
 */

namespace Com;


use Aitifen\Library\Teaching\LectureFiles;
use Aitifen\Library\Teaching\RoomSegment;
use Aitifen\Library\Teaching\StudentAnswer;
use Aitifen\Library\Teaching\TeacherNotes;

class LectureService
{

    /**
     * 根据讲义id获取讲义内容并组成ppt翻页的结构
     * @param $lectureId
     * @return mixed
     */
    public static function getLectureContent ($lectureId)
    {
        $data['modules'] = LectureFiles::getLecturePPT ($lectureId);
        return $data;
    }

    /**
     * 收录答案
     * @param $params
     * @return mixed
     */
    public static function collectionAnswer ($params)
    {
        $data = $params;
        $res = StudentAnswer::save ($data);
        if (!$res) {
            throw new \Exception(setEexcetion ('保存失败', 301));
        }
        return true;
    }

    /**
     * 根据学员ID和题ID获取答案
     * @param $params
     * @return mixed
     */
    public static function getStudentAnswer ($params)
    {
        $condition = [
            'student_id' => $params['student_id'],
            'quesiotn_id' => $params['quesiotn_id'],
        ];

        return StudentAnswer::getStudentAnswer  ($condition);
    }

    /**
     * 保存教师笔记
     * @param $params
     * @return bool
     */
    public static function saveTeacherNote ($params)
    {
        //根据房间号查询小组课一对一和课节id
        $condition = [
            'serial' => $params['serial'],
        ];
        $roomSegment = RoomSegment::getRoomSegment ($condition);
        if (empty($roomSegment)) {
            throw new \Exception(setEexcetion ('未找到该房间信息', 301));
        }

        $data = $params;
        $data['segment_id'] = $roomSegment[0]['segment_id'];
        $data['course_type'] = $roomSegment[0]['course_type'];
        $res = TeacherNotes::save ($data);
        if (!$res) {
            throw new \Exception(setEexcetion ('保存失败', 301));
        }
        return true;
    }

    /**
     * 根据教室号获取教室笔记列表
     * @param $params
     * @return array
     */
    public static function getTeacherNote ($params)
    {
        //根据房间号查询小组课一对一和课节id
        $condition = [
            'serial'    => $params['serial'],
            'is_remove' => 0,
        ];

        return TeacherNotes::getTeacherNotesAll ($condition, 'page asc', 'id,note_url,page');
    }

    /**
     * 根据ID删除教师笔记
     * @param $params
     * @return array|bool
     */
    public static function delTeacherNote ($params)
    {
        //根据房间号查询小组课一对一和课节id
        $condition = [
            'id' => $params['id'],
        ];
        $data = [
            'is_remove' => 1,
        ];

        $res = TeacherNotes::save ($data, $condition);
        if (!$res) {
            throw new \Exception(setEexcetion ('删除失败', 301));
        }
        return true;
    }
}