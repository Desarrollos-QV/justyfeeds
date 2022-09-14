/**
 * 
 * Main App Component
 *  
 * 
 */ 
"use strict";
/* global app, mediaSettings, Dropzone, trans, launchToast */

// Disable dropzone uploader auto loading globally as we will instantiate it manually
Dropzone.autoDiscover = false;

var FileUpload = {

    attachaments: [],
    myDropzone : null,
    isLoading:false,
    state: {}, 
    /**
     * Instantiates the media uploader plugin
     * @param selector
     * @param url
     */
    initDropZone:function (selector,url) {

        FileUpload.myDropzone = new Dropzone(selector, {
            paramName: "file", // The name that will be used to transfer the file
            previewTemplate: document.querySelector('#tpl').innerHTML,
            url: app.baseUrl + url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            clickable:['.file-upload-button'],
            previewsContainer: ".dropzone-previews",
            maxFilesize: mediaSettings.max_file_upload_size, // MB
            addRemoveLinks: true,
            dictRemoveFile: "<button class='btn btn-outline-primary mb-0' style='width: 100%;'>Delete</button>",
            acceptedFiles: mediaSettings.allowed_file_extensions,
            init: function() {
                // FileUpload.attachaments
                FileUpload.attachaments.map((element)=>{
                    var mockFile = { 
                        name: element.attachmentID, 
                        upload:{attachmentID:element.attachmentID} , 
                        type:element.type, 
                        thumbnail: element.thumbnail,
                    };
                    this.emit("addedfile", mockFile);
                    this.emit("thumbnail", mockFile, element.thumbnail);
                    this.emit("complete", mockFile);  
                }); 
                var _this = this;
                $(".draft-clear-button").on("click", function() {
                    _this.removeAllFiles(true);
                });
            }
        });

        FileUpload.myDropzone.on("addedfile", file => {
            let filePreview = $(file.previewElement);  
            $('.upload-text').css("display", "none");
            $('.upload-card').css("display", "flex");

            switch (file.type) {
                case 'video/mp4':
                case 'video/avi':
                case 'video/quicktime':
                case 'video/x-m4v':
                case 'video/mpeg':
                case 'video/wmw':
                case 'video/x-matroska':
                case 'video/x-ms-asf':
                case 'video/x-ms-wmv':
                case 'video/x-ms-wmx':
                case 'video/x-ms-wvx':
                case 'video':  
                    filePreview.find('.video-preview-item').remove();
                    filePreview.prepend(videoPreview()); 
                    FileUpload.generateThumbnail(file,true);
                    FileUpload.isLoading = true;
                    break;
                default:
                    filePreview.prepend(imagePreview());
                    break;
            }
        });

        FileUpload.myDropzone.on("totaluploadprogress", function (progress) {  
            var elem = document.querySelector('.circlePercent'); 

            if (elem) {
                (function animate() {
                    setProgress(elem, progress.toFixed(0));
                })();
            }
            
        });

        FileUpload.myDropzone.on("success", (file, response) => {  
            if(response.success){
                file.upload.attachmentID = response.attachmentID;
                FileUpload.blob_pic = FileUpload.blob_pic;
                FileUpload.attachaments.push({
                    attachmentID: response.attachmentID, 
                    path: response.path, 
                    type:response.type, 
                    thumbnail:response.thumbnail
                });
                // If received file is a converted video
                switch (file.type) {
                case 'video/mp4':
                case 'video/avi':
                case 'video/quicktime':
                case 'video/x-m4v':
                case 'video/mpeg':
                case 'video/wmw':
                case 'video/x-matroska':
                case 'video/x-ms-asf':
                case 'video/x-ms-wmv':
                case 'video/x-ms-wmx':
                case 'video/x-ms-wvx':
                case 'video':
                    FileUpload.updatePreviewElement(file, true,response);
                    break;
                default:
                    $('.preview_pic_blob').attr('src', response .thumbnail);
                    $('.preview_pic_blob').css('display', 'block');  
                    break;
                }
            }
            FileUpload.isLoading = false;
        });

        FileUpload.myDropzone.on("removedfile", function(file) {
            FileUpload.attachaments = FileUpload.attachaments.filter((attachment)=>{
                if(attachment.attachmentID !== file.upload.attachmentID){
                    return attachment;
                }
                else{
                    FileUpload.removeAttachment(attachment);
                }
                $('.upload-card').css("display", "none");
                $('.upload-text').css("display", "flex");
                $('.preview_pic_blob').attr('src', ' ');
                $('.preview_pic_blob').css('display', 'none');
                $('.post-create-button').attr('disabled',true);
            });
        });

        FileUpload.myDropzone.on("error", (file, errorMessage) => {
            if(typeof errorMessage.errors !== 'undefined'){
                // launchToast('danger',trans('Error'),errorMessage.errors.file)
                $.each(errorMessage.errors,function (field,error) {
                    launchToast('danger',trans('Error'),error);
                });
            }
            else{
                if(typeof errorMessage.message !== 'undefined'){
                    launchToast('danger',trans('Error'),errorMessage.message);
                }
                else{
                    launchToast('danger',trans('Error'),errorMessage);
                }
            }
            FileUpload.myDropzone.removeFile(file);
            FileUpload.isLoading = false;
            $('.upload-card').css("display", "none");
            $('.upload-text').css("display", "flex");
            $('.preview_pic_blob').css('display', 'none');
            $('.post-create-button').attr('disabled',true);
        });
    },

    /**
     * Updates the preview template based on uploaded file
     * @param file
     * @param localFile
     * @param attachment
     */
    updatePreviewElement:function (file,localFile, attachment = false) { 
        let filePreview = $(file.previewElement);   
        switch (file.type) {
        case 'video/mp4':
        case 'video/avi':
        case 'video/quicktime':
        case 'video/x-m4v':
        case 'video/mpeg':
        case 'video/wmw':
        case 'video/x-matroska':
        case 'video/x-ms-asf':
        case 'video/x-ms-wmv':
        case 'video/x-ms-wmx':
        case 'video/x-ms-wvx':
        case 'video':  
            if(localFile){
                filePreview.find('.video-preview-item').remove();
                setTimeout(() => {
                    filePreview.prepend(videoInitPreview(attachment.path));
                }, 800);
                $('.post-create-button').removeAttr('disabled');  
                FileUpload.generateThumbnail(file,localFile);
            }
            break;
        case 'audio/mpeg':
        case 'audio/ogg':
        case 'audio':
            filePreview.prepend(audioPreview());
            filePreview.addClass("w-100");
            filePreview.find('audio').addClass("w-100");
            filePreview.find(".audio-preview-item").addClass("w-100");
            var audioPreviewEl = filePreview.find('audio').get(0);
            filePreview.addClass("w-100");
            if(localFile){
                FileUpload.setMediaSourceForPreviewByElementAndFile(audioPreviewEl, file);
            }
            else{
                FileUpload.setPreviewSource(audioPreviewEl, file, attachment);
            }
            break;
        default:
            filePreview.prepend(imagePreview());
            if(!localFile){
                let previewElement = filePreview.find('img').get(0);
                FileUpload.setPreviewSource(previewElement, file, attachment);
            }
            break;
        }
    },

    /**
     * Sets up the media src for the uploaded file type
     * @param element
     * @param file
     * @returns {boolean}
     */
    setMediaSourceForPreviewByElementAndFile: function (element, file) {
        if(typeof element === 'undefined'){ return false;}
        if (element.canPlayType(file.type) !== "no") {
            const fileURL = window.URL.createObjectURL(file);
            $(element).on('loadeddata', function () {
                window.URL.revokeObjectURL(fileURL);
            });
            $(element).attr('src', fileURL);
            $(element).attr('type',file.type);
        }
    },

    /**
     * Sets media source | Thumbnail
     * @param element
     * @param file
     * @param attachment
     */
    setPreviewSource: function (element, file, attachment) { 
        $(element).attr('src', attachment.thumbnail);
        
    },

    /**
     * Generate Thumbnail on video
     * @param {*} element 
     */
    generateThumbnail: async function (element,file){ 
        let blob = URL.createObjectURL(element); 
        const thumbUrl = await getThumbnailForVideo(blob);  
        FileUpload.blob_pic = thumbUrl;
        $('.preview_pic_blob').attr('src', thumbUrl);
        $('.preview_pic_blob').css('display', 'block');  

        // __video_metadata_thumbnails__.getThumbnails(element).then(function(thumbnails) { 
        //     let blob = URL.createObjectURL(thumbnails[0].blob); 
        //     getBase64FromUrl(blob).then(data => {   
        //         FileUpload.blob_pic = data; 
        //         $('.preview_pic_blob').attr('src', data);
        //         $('.preview_pic_blob').css('display', 'block'); 
        //     }); 
        // });
    },

    /**
     * Removes an attached file
     * @param attachmentID
     */
    removeAttachment: function (attachmentID) {
        $.ajax({
            type: 'POST',
            data: {
                'attachmentId': attachmentID,
            },
            url: app.baseUrl+'/attachment/remove',
            success: function () {
                launchToast('success',trans('Success'), trans('Attachment removed.'));
            },
            error: function () {
                launchToast('danger',trans('Error'), trans('Failed to remove the attachment.'));
            }
        });
    },

}; 

/**
 * Video preview Component
 * @returns {string}
*/
function videoPreview() { 
    return `<div class="video-preview-item shadow">
                <div class="dz-progress">
                    <div class="dz-upload circlePercent">
                        <div class="counter" data-percent="0"></div>
                        <div class="progress"></div>
                        <div class="progressEnd"></div>
                    </div>
                </div>
            </div>`;
}

function videoInitPreview(path) { 
    return `<div class="video-preview-item shadow">
                <video class="video_tmp" src="`+path+`" autoplay muted loop ></video>
            </div>`;
}

function setProgress(elem, percent) {
    var
      degrees = percent * 3.6,
      transform = /MSIE 9/.test(navigator.userAgent) ? 'msTransform' : 'transform';
    elem.querySelector('.counter').setAttribute('data-percent', Math.round(percent));
    elem.querySelector('.progressEnd').style[transform] = 'rotate(' + degrees + 'deg)';
    elem.querySelector('.progress').style[transform] = 'rotate(' + degrees + 'deg)';
    if(percent >= 50 && !/(^|\s)fiftyPlus(\s|$)/.test(elem.className))
      elem.className += ' fiftyPlus';
}

/**
 * Image preview Component
 * @returns {string}
 */
function imagePreview() {
    return `<div class="dz-image shadow">
                <img data-dz-thumbnail/>
            </div>
            <div class="dz-details">
                <div class="dz-filename"><span data-dz-name></span></div>
                <div class="dz-size" data-dz-size></div>
            </div>`;
}

/**
 * Audio preview Component
 * @returns {string}
 */
function audioPreview() {
    return `<div class="audio-preview-item">
                    <span data-dz-name></span>
                    <span data-dz-size></span>
                <audio id="audio-preview" controls type="audio/mpeg" autoplay muted></audio>
        </div>`;
}

/**
 * Create thumbnail
 * @param {*} videoUrl 
 * @returns 
 */
async function getThumbnailForVideo(videoUrl) {
    const video = document.createElement("video");
    const canvas = document.createElement("canvas");
    video.style.display = "none";
    canvas.style.display = "none";
  
    // Trigger video load
    await new Promise((resolve, reject) => {
      video.addEventListener("loadedmetadata", () => {
        video.width = video.videoWidth;
        video.height = video.videoHeight;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        // Seek the video to 25%
        video.currentTime = video.duration * 0.0;
      });
      video.addEventListener("seeked", () => resolve());
      video.src = videoUrl;
    });
  
    // Draw the thumbnailz
    canvas
      .getContext("2d")
      .drawImage(video, 0, 0, video.videoWidth, video.videoHeight);

    const imageUrl = canvas.toDataURL("image/png");
    return getBase64FromUrl(imageUrl); 
  }



/**
 * Downloader Blob IMG
 * @param {*} url 
 * @returns 
 */
async function getBase64FromUrl(url) {
    const data = await fetch(url);
    const blob = await data.blob();
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.readAsDataURL(blob); 
      reader.onloadend = () => {
        const base64data = reader.result;   
        resolve(base64data);
      }
    });
}