package by.donmap.app

import android.app.Application
import by.donmap.app.api.ApiClient
import by.donmap.app.locale.LocaleManager

class DonMapApp : Application() {
    lateinit var apiClient: ApiClient
        private set

    lateinit var localeManager: LocaleManager
        private set

    override fun onCreate() {
        super.onCreate()
        localeManager = LocaleManager(this)
        apiClient = ApiClient(BuildConfig.API_BASE_URL)
    }
}
