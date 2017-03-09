<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-14
 * Time: 15:07
 * Desc: 微信系统错误代码列表
 */

namespace ZeroWeChat;


class ErrorCode
{
    const OK = 0;

    const APPID_MISSING          = 40001;  // 缺少appid
    const APPID_VALIDATE_ERR     = 40002;  // appid校验错误
    const ACCESS_TOKEN_MISSING   = 40003;  // 缺少access_token

    const SIGNATURE_VALIDATE_ERR = 41001;  // 签名验证错误
    const SIGNATURE_COMPUTE_ERR  = 41002;  // sha加密生成签名失败
    const XML_PARSE_ERR          = 41003;  // xml解析失败
    const XML_GENERATE_ERR       = 41004;  // 生成xml失败
    const AES_KEY_ILLEGAL        = 41005;  // encodingAesKey非法
    const AES_ENCRYPT_ERROR      = 41006;  // aes加密失败
    const AES_DECRYPT_ERR        = 41007;  // aes解密失败
    const BUFFER_ILLEGAL         = 41008;  // 解密后得到的buffer非法
    const BASE64_ENCODE_ERR      = 41009;  // base64加密失败
    const BASE64_DECODE_ERR      = 41010;  // base64解密失败
}