package hu.paksiinformatika.mobilblokkolo

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

data class WorkTimeItem(
    val employeeName: String,
    val eventsText: String,
    val workedTimeText: String
)

class WorkTimeAdapter(
    private val items: List<WorkTimeItem>
) : RecyclerView.Adapter<WorkTimeAdapter.WorkTimeViewHolder>() {

    class WorkTimeViewHolder(view: View) : RecyclerView.ViewHolder(view) {

        val tvEmployeeName: TextView =
            view.findViewById(R.id.tvWorkEmployeeName)

        val tvEvents: TextView =
            view.findViewById(R.id.tvWorkEvents)

        val tvWorkedTime: TextView =
            view.findViewById(R.id.tvWorkedTime)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): WorkTimeViewHolder {

        val view = LayoutInflater
            .from(parent.context)
            .inflate(R.layout.item_work_time, parent, false)

        return WorkTimeViewHolder(view)
    }

    override fun onBindViewHolder(
        holder: WorkTimeViewHolder,
        position: Int
    ) {
        val item = items[position]

        holder.tvEmployeeName.text = item.employeeName
        holder.tvEvents.text = item.eventsText
        holder.tvWorkedTime.text = item.workedTimeText
    }

    override fun getItemCount(): Int =
        items.size
}