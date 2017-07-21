<?php

namespace yiicod\socketio;

use yii\web\AssetBundle;

/**
 * Access Message asset bundle.
 *
 * @author Dmitry Turchanin
 */
class SocketIoAsset extends AssetBundle
{
    public $sourcePath = '@vendor/yiicod/yii2-socketio/server/node_modules/socket.io-client/dist';

    public $js = ['socket.io.js'];

    public $depends = [
    ];
}
