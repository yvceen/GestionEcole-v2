package com.myedu.schoolapp;

import android.app.DownloadManager;
import android.content.Context;
import android.content.SharedPreferences;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.webkit.CookieManager;
import android.webkit.URLUtil;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.widget.Toast;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    private static final String PREFS_NAME = "myedu_android";
    private static final String PREF_LAST_VERSION_CODE = "last_version_code";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        configureWebViewForProduction();
    }

    @Override
    public void onBackPressed() {
        WebView webView = getCurrentWebView();
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
            return;
        }

        super.onBackPressed();
    }

    private void configureWebViewForProduction() {
        WebView webView = getCurrentWebView();
        if (webView == null) {
            return;
        }

        clearWebCacheAfterAppUpdate(webView);

        WebSettings settings = webView.getSettings();
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setSupportZoom(false);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            cookieManager.setAcceptThirdPartyCookies(webView, false);
        }

        webView.setDownloadListener((url, userAgent, contentDisposition, mimeType, contentLength) -> {
            try {
                String fileName = URLUtil.guessFileName(url, contentDisposition, mimeType);
                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                if (mimeType != null && !mimeType.isEmpty()) {
                    request.setMimeType(mimeType);
                }

                String cookies = cookieManager.getCookie(url);
                if (cookies != null && !cookies.isEmpty()) {
                    request.addRequestHeader("Cookie", cookies);
                }

                if (userAgent != null && !userAgent.isEmpty()) {
                    request.addRequestHeader("User-Agent", userAgent);
                }

                request.setTitle(fileName);
                request.setDescription("Telechargement MyEdu");
                request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName);

                DownloadManager downloadManager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                if (downloadManager != null) {
                    downloadManager.enqueue(request);
                    Toast.makeText(this, "Telechargement lance", Toast.LENGTH_SHORT).show();
                }
            } catch (Exception exception) {
                Toast.makeText(this, "Impossible de lancer le telechargement", Toast.LENGTH_LONG).show();
            }
        });
    }

    private WebView getCurrentWebView() {
        return bridge != null ? bridge.getWebView() : null;
    }

    private void clearWebCacheAfterAppUpdate(WebView webView) {
        int currentVersionCode = getCurrentVersionCode();
        if (currentVersionCode <= 0) {
            return;
        }

        SharedPreferences preferences = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
        int lastVersionCode = preferences.getInt(PREF_LAST_VERSION_CODE, 0);
        if (lastVersionCode > 0 && lastVersionCode < currentVersionCode) {
            webView.clearCache(false);
        }

        if (lastVersionCode != currentVersionCode) {
            preferences.edit().putInt(PREF_LAST_VERSION_CODE, currentVersionCode).apply();
        }
    }

    private int getCurrentVersionCode() {
        try {
            PackageInfo packageInfo = getPackageManager().getPackageInfo(getPackageName(), 0);
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                return (int) packageInfo.getLongVersionCode();
            }

            return packageInfo.versionCode;
        } catch (PackageManager.NameNotFoundException exception) {
            return 0;
        }
    }
}
