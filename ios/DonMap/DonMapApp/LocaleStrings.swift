import Foundation

enum LocaleStrings {
    static func load() -> [String: Any] {
        guard
            let url = Bundle.main.url(forResource: "ru", withExtension: "json", subdirectory: "locales"),
            let data = try? Data(contentsOf: url),
            let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
        else { return [:] }
        return json
    }

    static func t(_ key: String, from root: [String: Any] = LocaleStrings.load()) -> String {
        let parts = key.split(separator: ".").map(String.init)
        var node: Any = root
        for part in parts {
            guard let dict = node as? [String: Any], let next = dict[part] else { return key }
            node = next
        }
        return node as? String ?? key
    }
}
