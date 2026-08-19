package hu.paksiinformatika.mobilblokkolo

import android.content.Intent
import android.graphics.BitmapFactory
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.EventEntity
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

data class EventListItem(
    val event: EventEntity,
    val employeeName: String
)

class EventAdapter(
    private val items: List<EventListItem>
) : RecyclerView.Adapter<EventAdapter.EventViewHolder>() {

    class EventViewHolder(
        itemView: View
    ) : RecyclerView.ViewHolder(itemView) {

        val imgPhoto: ImageView =
            itemView.findViewById(R.id.imgPhoto)

        val tvEmployeeName: TextView =
            itemView.findViewById(R.id.tvEmployeeName)

        val tvDate: TextView =
            itemView.findViewById(R.id.tvDate)

        val tvGps: TextView =
            itemView.findViewById(R.id.tvGps)

        val tvSyncStatus: TextView =
            itemView.findViewById(R.id.tvSync)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): EventViewHolder {

        val view =
            LayoutInflater
                .from(parent.context)
                .inflate(
                    R.layout.item_event,
                    parent,
                    false
                )

        return EventViewHolder(view)
    }

    override fun onBindViewHolder(
        holder: EventViewHolder,
        position: Int
    ) {

        val item =
            items[position]

        val event =
            item.event

        holder.tvEmployeeName.text =
            item.employeeName

        val formatter =
            SimpleDateFormat(
                "yyyy-MM-dd HH:mm:ss",
                Locale.getDefault()
            )

        holder.tvDate.text =
            formatter.format(
                Date(event.timestamp)
            )

        holder.tvGps.text =
            if (
                event.latitude != null &&
                event.longitude != null
            ) {
                "GPS: ${event.latitude}, ${event.longitude}"
            } else {
                "GPS: nincs adat"
            }

        holder.tvSyncStatus.text =
            if (event.synced) {
                "Szinkronizálva"
            } else {
                "Nincs szinkronizálva"
            }

        if (event.photoPath != null) {

            val bitmap =
                BitmapFactory.decodeFile(
                    event.photoPath
                )

            holder.imgPhoto.setImageBitmap(
                bitmap
            )

            holder.imgPhoto.setOnClickListener {

                val intent =
                    Intent(
                        holder.itemView.context,
                        PhotoViewerActivity::class.java
                    )

                intent.putExtra(
                    "photoPath",
                    event.photoPath
                )

                holder.itemView.context
                    .startActivity(intent)
            }

        } else {

            holder.imgPhoto.setImageDrawable(
                null
            )

            holder.imgPhoto.setOnClickListener(
                null
            )
        }
    }

    override fun getItemCount(): Int =
        items.size
}