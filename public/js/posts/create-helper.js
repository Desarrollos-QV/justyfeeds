/**
 * Post create (helper) component
 */
"use strict";
/* global app, Post, user, FileUpload, updateButtonState, launchToast, trans, redirect */

$(function () {
    $("#post-price").keypress(function(e) {
        if(e.which == 13) {
            PostCreate.savePostPrice();
        }
    });
})

var PostCreate = {
    // Paid post price
    postPrice : 0,
    /**
     * Shows up the post price setter dialog
     */
    showSetPricePostDialog: function(){
        $('#post-set-price-dialog').modal('show');
    },
    /**
     * Saves the post price into the state
     */
    savePostPrice: function(){
        PostCreate.postPrice = $('#post-price').val();
        if(parseInt(PostCreate.postPrice) <= 0){
            $('#post-price').addClass('is-invalid');
            return false;
        }
        $('.post-price-label').html('('+(app.currencySymbol ? app.currencySymbol : '' )+PostCreate.postPrice+(app.currencySymbol ? '' : '' + app.currency)+')');
        $('#post-set-price-dialog').modal('hide');
        $('#post-price').removeClass('is-invalid');
    },
    /**
     * Clears up post price
     */
    clearPostPrice: function(){
        PostCreate.postPrice = 0;
        $('#post-price').val(0);
        $('.post-price-label').html('');
        $('#post-set-price-dialog').modal('hide');
    },

    /**
     * Initiates the post draft data, if available
     * @param data
     * @param type
     */
    initPostDraft: function(data, type = 'draft'){
        Post.initialDraftData = Post.draftData;
        if(data){
            Post.draftData = data;
            if(type === 'draft'){
                FileUpload.attachaments = data.attachments;
            }
            else{
                data.attachments.map(function (item) {
                    FileUpload.attachaments.push({
                        attachmentID: item.id, 
                        path: item.path, 
                        type:item.attachmentType, 
                        thumbnail:item.thumbnail
                    });
                });
            }
            $('#dropzone-uploader').val(Post.draftData.text);
        }
    },

    /**
     * Clears up post draft data
     */
    clearDraft: function(){
        // Clearing attachments from the backend
        Post.draftData.attachments.map(function (value) {
            FileUpload.removeAttachment(value.attachmentID);
        });
        // Removing previews
        $('.dropzone-previews .dz-preview ').each(function (index, item) {
            $(item).remove();
        });
        $('.upload-card').css("display", "none");
        $('.upload-text').css("display", "flex");
        $('.preview_pic_blob').attr('src', ' ');
        $('.preview_pic_blob').css('display', 'none');
        $('.post-create-button').attr('disabled',true);
        // Clearing Fileupload class attachments
        FileUpload.attachaments = [];
        // Clearing up the local storage object
        PostCreate.clearDraftData();
        // Clearing up the text area value
        $('#dropzone-uploader').val(Post.draftData.text);
    },

    /**
     * Saves post draft data
     */
    saveDraftData: function(){
        Post.draftData.attachments = FileUpload.attachaments;
        Post.draftData.text = $('#dropzone-uploader').val();
        localStorage.setItem('draftData', JSON.stringify(Post.draftData));
    },

    /**
     * Clears up draft data
     * @param callback
     */
    clearDraftData: function(callback = null){
        localStorage.removeItem('draftData');
        Post.draftData = Post.initialDraftData;
        if(callback !== null){
            callback;
        }
    },


    /**
     * Populates create/edit post form with draft data
     * @returns {boolean|any}
     */
    populateDraftData: function(){
        const draftData = localStorage.getItem('draftData');
        if(draftData){
            return JSON.parse(draftData);
        }
        else{
            return false;
        }
    },

    /**
     * Save new / update post
     * @param type
     * @param postID
     */
    save: function (type = 'create', postID = false, forceSave = false) {
        if(FileUpload.isLoading === true && forceSave === false){
            $('.confirm-post-save').unbind('click');
            $('.confirm-post-save').on('click',function () {
                PostCreate.save(type, postID, true);
            });
            $('#confirm-post-save').modal('show');
            return false;
        }
        
        updateButtonState('loading',$('.post-create-button'));
        let route = app.baseUrl + '/posts/save';
        let data = {
            'attachments': FileUpload.attachaments,
            'text': $('#dropzone-uploader').val(),
            'price': PostCreate.postPrice,
            'blob_pic' : FileUpload.blob_pic //b64ToBlob(FileUpload.blob_pic.split(',')[1], 'image/png'),
        };
        if(type === 'create'){
            data.type = 'create';
        }
        else{
            data.type = 'update';
            data.id = postID;
        }
        
        console.log(data);


        console.log(route);
        $.ajax({
            type: 'POST',
            data: data,
            url: route,
            success: function (data) {  
                if(type === 'create'){
                    PostCreate.clearDraftData(redirect(app.baseUrl+'/'+user.username));
                }
                else{
                    redirect(app.baseUrl+'/posts/'+postID+'/'+user.username);
                }

                updateButtonState('loaded',$('.post-create-button'), trans('Save'));
                $('#confirm-post-save').modal('hide');
            },
            error: function (result) {
                if(result.status === 422 || result.status === 500) {
                    $.each(result.responseJSON.errors, function (field, error) {
                        if (field === 'text') {
                            $('#dropzone-uploader').addClass('is-invalid');
                            $('#dropzone-uploader').focus();
                        }
                        if(field === 'permissions'){
                            launchToast('danger',trans('Error'),error);
                        }
                    });
                }
                else if(result.status === 403){
                    launchToast('danger',trans('Error'),'Post not found.');
                }
                $('#confirm-post-save').modal('hide');
                updateButtonState('loaded',$('.post-create-button'), trans('Save'));
            }
        });
    },

};

/**
 * convertir base64 a archivo binario
 * @param {*} b64Data 
 * @param {*} contentType 
 * @returns 
 */
 function b64ToBlob(b64Data, contentType) {
    contentType = contentType || ''
 
    var byteCharacters = atob (b64Data) // decodifica datos base64 en una cadena binaria
    var buffer = [] // Tenga en cuenta que el primer parámetro de Blob debe ser una matriz
 
    // La matriz de tipos se usa para procesar archivos binarios
    var aBuffer = new ArrayBuffer(byteCharacters.length)
    var uBuffer = new Uint8Array(aBuffer)
    for (var i = 0; i < byteCharacters.length; i++) {
        uBuffer [i] = byteCharacters.charCodeAt (i) // Obtenga el código Unicode y guárdelo en la matriz de tipos
    }
    buffer.push(uBuffer)
        // Las matrices ordinarias no pueden generar archivos binarios
        var blob = new Blob (buffer, {// Genera un archivo binario
        type: contentType
    });
    
    return blob;
}