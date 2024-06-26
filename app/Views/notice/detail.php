<?php
    $session = session();
    $detail = $data['detail']['data'];
    $files = $data['file'];
    $title_photo = null;
    $photos = array();

    if ( isset( $files['image'] ) ) {
        $title_photo = $files['image'][0]->FILE_PATH . "/" . $files['image'][0]->FILE_NM . "." . $files['image'][0]->FILE_EXT;

        $photos = [];
        foreach ($files['image'] as $file){

            if ( ! $file->FILE_URL ) {
                $filepath = $file->FILE_PATH . "/" . $file->FILE_NM . "." . $file->FILE_EXT;
                $filepath =  str_replace( _ROOT_PATH , '' , $filepath ) ;
            }else {
                $filepath = $file->FILE_URL;
            }
            $filesrc = str_replace('//','/',$filepath);
            $filepath = WRITEPATH . $filesrc;
            
            $photos[] = [
                'link' => $filesrc,
                'orgfilename' => $file->ORIGIN_FILE_NM,
                'size' => $file->FILE_SIZE,
                'file_seq' => $file->APND_FILE_SEQ,
                // 'thumbnail' => $file->THUMBNAIL == "Y" ? $file->FILE_PATH . "/" . $file->FILE_NM . ".jpg" : $file->FILE_PATH . "/" . $file->FILE_NM . "." . $file->FILE_EXT
                'thumbnail' => getThumbnailPreview($filepath)
            ];
        }
    }


?>
<link rel="stylesheet" href="/resources/justifiedGallery.min/justifiedGallery.min.css" />
<script src="/resources/justifiedGallery.min/jquery.justifiedGallery.min.js"></script>
<!-- content -->
<div class="sub_content notice_content notice_detail">
    <div class="sub_inner">
        
        <div class="notice_detail_list detail_list" id="detail">
        <div class="detail_cont">
        <div class="t_info">
            <div class="notice_title title"><span><?php echo $detail->TITLE; ?></span></div>
            <div class="notice_author author"><span><?php echo $detail->WRITE_NM; ?></span></div>
            <div class="notice_date date"><span><?php echo $detail->ENT_DTTM; ?></span></div>
        </div>
        
        <?php if ($title_photo !== null) :?>
        <!-- 앨범 목록에 있는 사진 불러오기 -->
        <div class="detail_img" id="mygallery">
            <!-- 첨부 이미지 -->
            <!-- 앨범 목록에 있는 사진 불러오기 -->
            <?php foreach ( $photos as $photo ) : ?>
            <a>
                <img alt="<?php echo $photo['orgfilename']?>" src="<?php echo base_url($photo['thumbnail'])?>" class="previewImage previewPhoto-<?php echo $noti_seq;?>" data-src="<?php echo base_url($photo['link'])?>" data-id="<?php echo $noti_seq;?>" />
            </a>
            <?php endforeach;?>
        </div>
        <?php endif; ?>
        <!-- <div class="detail_file">
            <% _.each( file.file , function ( item , key ,list ) { %>
                <a href="<%= item.FILE_PATH %>/<%= item.FILE_NM %>.<%= item.FILE_EXT %>" download><%= item.ORIGIN_FILE_NM %></a><br>
            <% }) %>
        </div> -->
        <div class="detail_txt">
            <?php echo $detail->CNTS; ?>
        </div>
    </div>
        </div>
        <!-- [ 교사앱 : 교사앱에서만 보이기 - 알림장 수정/삭제 ] -->
        <?php if ($detail->USER_ID == $session->get('_user_id') ) :?>
        <div class="btn_box" style="margin-top: auto; " id="btn_box">
            <button type="button" class="edit left" onclick="goEdit();">수정</button>
            <button type="button" class="del right" onclick="goDelete();">삭제</button>
        </div>
        <!-- [ 교사앱 : 삭제 모달 ]  -->
        <div class="modal">
            <div class="cont">
                <p>삭제하시겠습니까?</p>
                <div class="btn">
                    <button class="cancel">취소</button>
                    <button type="button" class="confirm">확인</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- //content -->
<!-- Flipbook main Js file -->
<!-- <script src="/resources/dflip/js/libs/jquery.min.js" type="text/javascript"></script> -->

<script type="text/javascript">
var _rowHeight = 243;
    $("#mygallery").justifiedGallery({
                rowHeight: _rowHeight,
                maxRowHeight: 0,
                margins: 1,
                border: 0,
                lastRow: 'left',
                captions: true,
                randomize: false
            });



function goEdit(){
    location.href="/notice/<?php echo $detail->NOTI_SEQ;?>/edit";
}

function goDelete(){
    Swal.fire({
        title: "알림장",
        text: "현재 알림장을 삭제 하시겠습니까?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "확인",
        cancelButtonText: "취소"

    }).then((result) => {
        if (result.isConfirmed) {
            var content_data = {
                action : 'deleteProc',
                noti_seq : '<?php echo $noti_seq; ?>'
            }


            fetch("/api/notice", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(content_data),
                    })
            .then((response) => response.json())
            .then((data) => {
                console.log(data);
                return data;
            });



            const Toast = Swal.mixin({
                toast: true,
                position: 'center-center',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })
            Toast.fire({
                icon: 'success',
                title: '알림장이 삭제 되었습니다.'
            }).then(function (result) {
                if (true) {

                    location.href="/notice/"
                }
            });
        }
    });
}
</script>