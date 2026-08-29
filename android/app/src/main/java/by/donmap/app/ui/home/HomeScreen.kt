package by.donmap.app.ui.home

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import by.donmap.app.R
import by.donmap.app.api.ApiClient
import by.donmap.app.feature.listings.ListingItem
import by.donmap.app.feature.listings.ListingsRepository
import by.donmap.app.locale.LocaleManager

@Composable
fun HomeScreen(apiClient: ApiClient, localeManager: LocaleManager) {
    val repository = remember { ListingsRepository(apiClient) }
    var query by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(true) }
    var items by remember { mutableStateOf<List<ListingItem>>(emptyList()) }

    LaunchedEffect(query) {
        loading = true
        items = repository.search(query).getOrDefault(emptyList())
        loading = false
    }

    Column(modifier = Modifier.fillMaxSize()) {
        Header(localeManager)
        OutlinedTextField(
            value = query,
            onValueChange = { query = it },
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            placeholder = { Text(localeManager.t("search.placeholder")) },
            singleLine = true,
        )
        Row(modifier = Modifier.weight(1f)) {
            Box(modifier = Modifier.weight(1f)) {
                when {
                    loading -> LoadingState(localeManager)
                    items.isEmpty() -> EmptyState(localeManager)
                    else -> ListingColumn(items)
                }
            }
            MapPlaceholder(
                localeManager = localeManager,
                modifier = Modifier.weight(1f),
            )
        }
    }
}

@Composable
private fun Header(localeManager: LocaleManager) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.primary)
            .padding(16.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column {
            Text(
                text = localeManager.t("app.name"),
                style = MaterialTheme.typography.titleLarge,
                color = MaterialTheme.colorScheme.onPrimary,
            )
            Text(
                text = localeManager.t("app.tagline"),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onPrimary,
            )
        }
        Row {
            TextButton(onClick = {}) { Text(stringResource(R.string.nav_sale)) }
            TextButton(onClick = {}) { Text(stringResource(R.string.nav_rent)) }
            TextButton(onClick = {}) { Text(stringResource(R.string.nav_favorites)) }
        }
    }
}

@Composable
private fun ListingColumn(items: List<ListingItem>) {
    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        items(items) { item ->
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Text(text = item.title, style = MaterialTheme.typography.titleMedium)
                    Text(text = item.price, style = MaterialTheme.typography.bodyLarge)
                    Text(
                        text = "${item.rooms} комн. · ${item.area} м²",
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }
        }
    }
}

@Composable
private fun MapPlaceholder(localeManager: LocaleManager, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .fillMaxHeight()
            .background(MaterialTheme.colorScheme.surface)
            .padding(16.dp),
        contentAlignment = Alignment.Center,
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(text = localeManager.t("map.placeholder"), style = MaterialTheme.typography.titleMedium)
            Text(text = localeManager.t("map.hint"), style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun LoadingState(localeManager: LocaleManager) {
    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            CircularProgressIndicator()
            Text(text = localeManager.t("listing.loading"), modifier = Modifier.padding(top = 8.dp))
        }
    }
}

@Composable
private fun EmptyState(localeManager: LocaleManager) {
    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Text(text = localeManager.t("listing.noResults"))
    }
}
